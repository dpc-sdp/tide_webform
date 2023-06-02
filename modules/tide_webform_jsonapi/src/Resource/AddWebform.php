<?php

namespace Drupal\tide_webform_jsonapi\Resource;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\jsonapi\JsonApiResource\ErrorCollection;
use Drupal\jsonapi\JsonApiResource\JsonApiDocumentTopLevel;
use Drupal\jsonapi\JsonApiResource\LinkCollection;
use Drupal\jsonapi\JsonApiResource\NullIncludedData;
use Drupal\jsonapi\JsonApiResource\ResourceObject;
use Drupal\jsonapi\JsonApiResource\ResourceObjectData;
use Drupal\jsonapi\ResourceResponse;
use Drupal\jsonapi_resources\Resource\EntityQueryResourceBase;
use Drupal\tide_webform_jsonapi\TideWebformJsonapiHelper;
use Drupal\webform\Entity\Webform;
use Drupal\webform\WebformSubmissionForm;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Route;
use Drupal\jsonapi\ResourceType\ResourceTypeRepository;
use Drupal\jsonapi\Controller\EntityResource;

/**
 * Processes a request to create a submission.
 *
 * @package Drupal\jsonapi_resources_test\Resource
 */
final class AddWebform extends EntityQueryResourceBase implements ContainerInjectionInterface {

  /**
   * The ResourceTypeRepository.
   *
   * @var \Drupal\jsonapi\ResourceType\ResourceTypeRepository
   */
  protected $tideWebformJsonapiHelper;

  /**
   * The ResourceTypeRepository.
   *
   * @var \Drupal\jsonapi\ResourceType\ResourceTypeRepository
   */
  protected $resourceTypeRepository;

  /**
   * The EntityResource controller.
   *
   * @var \Drupal\jsonapi\Controller\EntityResource
   */
  protected $resource;

  /**
   * {@inheritdoc}
   */
  public function __construct(TideWebformJsonapiHelper $tide_webform_jsonapi_helper, ResourceTypeRepository $resource_type_repository, EntityResource $resource) {
    $this->tideWebformJsonapiHelper = $tide_webform_jsonapi_helper;
    $this->resourceTypeRepository = $resource_type_repository;
    $this->resource = $resource;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('tide_webform_jsonapi.helper'),
      $container->get('jsonapi.resource_type.repository'),
      $container->get('jsonapi.entity_resource')
    );
  }

  /**
   * Process the resource request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   * @param \Drupal\webform\Entity\Webform $webform
   *   The webform entity.
   *
   * @return \Drupal\jsonapi\ResourceResponse
   *   The response.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\ConflictHttpException
   *   Thrown when the entity to be created already exists.
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *   Thrown if the storage handler couldn't be loaded.
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   Thrown if the entity could not be saved.
   */
  public function process(Request $request, Webform $webform): ResourceResponse {
    // Purely business logic to get the webform_submission entity.
    $resource_type = $this->resourceTypeRepository->get(
      'webform_submission',
      $webform->id()
    );
    $reflectedMethod = new \ReflectionMethod(
      $this->resource,
      'deserialize'
    );
    $reflectedMethod->setAccessible(TRUE);
    /** @var \Drupal\webform\Entity\WebformSubmission $entity */
    $entity = $reflectedMethod->invoke(
      $this->resource,
      $resource_type,
      $request,
      JsonApiDocumentTopLevel::class
    );
    static::validate($entity);
    // It implies triggering the webform handlers.
    $entity->save();
    // Massage, organise and verify the data.
    $original_elements = $webform->getElementsDecodedAndFlattened();
    $supported_validations = $this->tideWebformJsonapiHelper->getSupportedValidateElements();
    $results = $this->tideWebformJsonapiHelper->webformValidateSettingsExtractor($supported_validations, $original_elements);
    $new_array = [];
    foreach ($results as $key => $r) {
      $new_array[$key] = $this->tideWebformJsonapiHelper->attachValidateSettingsToPayload($r);
    }
    $errors = $this->tideWebformJsonapiHelper->validatePayload($entity->getData(), $new_array, $original_elements);
    // Let webform core checks words and characters.
    $internal_errors = WebformSubmissionForm::validateWebformSubmission($entity);
    // Prepare error messages.
    if (!empty($internal_errors)) {
      foreach ($internal_errors as $key => $message) {
        $errors[$key][] = $message->__toString();
      }
    }
    $errors_collection = [];
    if (!empty($errors)) {
      foreach ($errors as $field_id => $details) {
        foreach ($details as $item) {
          $errors_collection[] = new HttpException(422, $field_id . '|' . $item);
        }
      }
      $errs = new ErrorCollection($errors_collection);
      $document = new JsonApiDocumentTopLevel($errs, new NullIncludedData(), new LinkCollection([]));
      return new ResourceResponse($document, 422);
    }

    // Return 201 if no errors.
    $resource_object = ResourceObject::createFromEntity($resource_type, $entity);
    $primary_data = new ResourceObjectData([$resource_object], 1);
    return $this->createJsonapiResponse($primary_data, $request, 201);
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteResourceTypes(Route $route, string $route_name): array {
    return $this->getResourceTypesByEntityTypeId('webform_submission');
  }

}
