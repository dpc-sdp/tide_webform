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
  public function __construct(ResourceTypeRepository $resource_type_repository, EntityResource $resource) {
    $this->resourceTypeRepository = $resource_type_repository;
    $this->resource = $resource;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
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
    // Purely business logic part to get the webform_submission entity.
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
    $supported_validations = $this->getSupportedValidateElements();
    $results = $this->webformValidateSettingsExtractor($supported_validations, $original_elements);
    $new_array = [];
    foreach ($results as $key => $r) {
      $new_array[$key] = $this->attachValidateSettingsToPayload($r);
    }
    $errors = $this->validatePayload($entity->getData(), $new_array);
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

  /**
   * Extracts validate settings from the webform settings.
   */
  public function webformValidateSettingsExtractor(array $supported_validation, array $original_elements): array {
    $res = [];
    // Iterate through webform values.
    foreach ($original_elements as $w_key => $items) {
      // Iterate through supported validation keys and their values.
      foreach ($supported_validation as $key => $value) {
        // If the current value is an array, check for a match with the key.
        if (is_array($value)) {
          if (array_key_exists($key, $items)) {
            $res[$w_key][$key] = $items[$key];
          }
        }
        else {
          // If the value exists in the items and is not an array,
          // store it in the result.
          if (array_key_exists($value, $items)) {
            $res[$w_key][$value] = $items[$value];
          }
        }
      }
    }
    return $res;
  }

  /**
   * Supported validators.
   */
  private function getSupportedValidateElements() {
    return [
      '#required',
      '#required_error',
      '#pattern',
      '#pattern_error',
    ];
  }

  /**
   * Attached supported validator to Payload.
   */
  private function attachValidateSettingsToPayload(array $payload) {
    $output  = [];
    $mapping = [
      '#required'                => 'required',
      '#required_error'          => 'required',
      '#pattern'                 => 'pattern',
      '#pattern_error'           => 'pattern',
    ];
    foreach ($payload as $key => $value) {
      if (isset($mapping[$key])) {
        $output[$mapping[$key]][] = $value;
      }
    }
    return $output;
  }

  /**
   * Verifies the payload.
   */
  private function validatePayload(array $payload, array $massaged_validates_array) {
    $results = [];
    foreach ($payload as $id => $value) {
      if (array_key_exists($id, $massaged_validates_array)) {
        if (!empty($this->generateErrorString($value, $massaged_validates_array[$id]))) {
          $results[$id] = $this->generateErrorString($value, $massaged_validates_array[$id]);
        }
      }
    }
    return $results;
  }

  /**
   * Generates error messages.
   */
  private function generateErrorString($value, array $arr) {
    $res = [];
    foreach ($arr as $k => $v) {
      if (call_user_func('tide_webform_jsonapi_' . $k . '_validate', $value, $v) !== TRUE) {
        $res[] = call_user_func('tide_webform_jsonapi_' . $k . '_validate', $value, $v);
      }
    }
    return $res;
  }

}
