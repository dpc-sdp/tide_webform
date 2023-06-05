<?php

namespace Drupal\Tests\tide_webform_jsonapi\Functional;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\field\Traits\EntityReferenceTestTrait;
use Drupal\Tests\jsonapi\Functional\JsonApiRequestTestTrait;
use Drupal\Tests\jsonapi\Functional\ResourceResponseTestTrait;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;
use GuzzleHttp\RequestOptions;

/**
 * Tests Tide webform jsonapi processor.
 *
 * @group jsonapi_resources
 */
class TideWebformJsonapiResourceTest extends BrowserTestBase {

  use JsonApiRequestTestTrait;
  use ResourceResponseTestTrait;
  use EntityReferenceTestTrait;

  /**
   * The account to use for authentication.
   *
   * @var null|\Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'basic_auth',
    'path',
    'webform',
    'serialization',
    'jsonapi',
    'jsonapi_resources',
    'tide_webform_jsonapi',
    'tide_webform_jsonapi_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Ensure the anonymous user role has no permissions at all.
    $user_role = Role::load(RoleInterface::ANONYMOUS_ID);
    foreach ($user_role->getPermissions() as $permission) {
      $user_role->revokePermission($permission);
    }
    $user_role->save();
    assert([] === $user_role->getPermissions(), 'The anonymous user role has no permissions at all.');

    // Ensure the authenticated user role has no permissions at all.
    $user_role = Role::load(RoleInterface::AUTHENTICATED_ID);
    foreach ($user_role->getPermissions() as $permission) {
      $user_role->revokePermission($permission);
    }
    $user_role->save();
    assert([] === $user_role->getPermissions(), 'The authenticated user role has no permissions at all.');
    $this->account = $this->createUser();
    $this->container->get('current_user')->setAccount($this->account);
  }

  /**
   * Tests the custom Add Webform resource.
   */
  public function testAddWebformResource() {
    $this->config('jsonapi.settings')->set('read_only', FALSE)->save(TRUE);
    $this->grantPermissionsToTestedRole([
      'access content',
    ]);
    $url = Url::fromRoute('tide_webform_jsonapi.add_webform',
      ['webform' => 'tide_webform_content_rating']);
    $request_options = [];
    $request_options[RequestOptions::HEADERS]['Accept'] = 'application/vnd.api+json';
    $request_options[RequestOptions::HEADERS]['Content-Type'] = 'application/vnd.api+json';
    $request_options = NestedArray::mergeDeep($request_options, $this->getAuthenticationRequestOptions());
    $normalization = [
      'data' => [
        'type'       => 'webform_submission--tide_webform_content_rating',
        'attributes' => [
          'remote_addr' => '1.2.3.4',
          'data'        => "url: '/home'\nwas_this_page_helpful: 'Yes'\ncomments: 'TEST\n Content Rating comment1'\ntestemail: 'w'\ntestextfield: ''\ntestemail: ''",
        ],
      ],
    ];
    $request_options[RequestOptions::BODY] = Json::encode($normalization);
    $response = $this->request('POST', $url, $request_options);
    $this->assertSame(422, $response->getStatusCode(), $response->getBody());
  }

  /**
   * Grants permissions to the authenticated role.
   *
   * @param string[] $permissions
   *   Permissions to grant.
   */
  protected function grantPermissionsToTestedRole(array $permissions) {
    $this->grantPermissions(Role::load(RoleInterface::AUTHENTICATED_ID), $permissions);
  }

  /**
   * Returns Guzzle request options for authentication.
   *
   * @return array
   *   Guzzle request options to use for authentication.
   *
   * @see \GuzzleHttp\ClientInterface::request()
   */
  protected function getAuthenticationRequestOptions() {
    return [
      'headers' => [
        'Authorization' => 'Basic ' . base64_encode($this->account->name->value . ':' . $this->account->passRaw),
      ],
    ];
  }

}
