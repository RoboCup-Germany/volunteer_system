<?php

declare(strict_types=1);

namespace Volunteersystem\Test\Feature\Controllers;

use Volunteersystem\Config\Config;
use Volunteersystem\Controllers\RegistrationController;
use Volunteersystem\Events\Listener\OAuth2;
use Volunteersystem\Models\VolunteerType;
use Volunteersystem\Models\BaseModel;
use Volunteersystem\Test\Feature\ApplicationFeatureTest;
use Volunteersystem\Test\Utils\FormFieldAssert;
use Volunteersystem\Test\Utils\SignUpConfig;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * @group registration-controller-tests
 */
final class RegistrationControllerTest extends ApplicationFeatureTest
{
    private Config $config;
    private SessionInterface $session;
    /**
     * @var OAuth2&MockObject
     */
    private OAuth2 $oauth;
    /**
     * @var Array<BaseModel>
     */
    private array $modelsToBeDeleted;
    private RegistrationController $subject;

    public function setUp(): void
    {
        parent::setUp();
        $this->modelsToBeDeleted = [];
        $app = app();
        $this->oauth = $this->getMockBuilder(OAuth2::class)
            ->disableOriginalConstructor()
            ->getMock();
        $app->instance(OAuth2::class, $this->oauth);
        $this->config = $app->get(Config::class);
        $this->session = $app->get(SessionInterface::class);
        $this->subject = $app->make(RegistrationController::class);
    }

    public function tearDown(): void
    {
        parent::tearDown();
        $this->deleteModels();
    }

    /**
     * Renders the registration page with a minimum fields config.
     * Asserts that the basic fields are there while the other fields are not there.
     *
     * @covers \Volunteersystem\Controllers\RegistrationController
     */
    public function testViewMinimumConfig(): void
    {
        SignUpConfig::setMinimumConfig($this->config);
        $response = $this->subject->view();

        $this->assertSame(200, $response->getStatusCode());
        $responseHTML = $response->getBody()->__toString();

        // assert the expected fields are there
        FormFieldAssert::assertContainsInputField('username', $responseHTML);
        FormFieldAssert::assertContainsInputField('password', $responseHTML);
        FormFieldAssert::assertContainsInputField('password_confirmation', $responseHTML);
        FormFieldAssert::assertContainsInputField('email', $responseHTML);
        FormFieldAssert::assertContainsInputField('mobile', $responseHTML);

        // assert the disabled fields are not there
        FormFieldAssert::assertNotContainsInputField('pronoun', $responseHTML);
        FormFieldAssert::assertNotContainsInputField('firstname', $responseHTML);
        FormFieldAssert::assertNotContainsInputField('lastname', $responseHTML);
        FormFieldAssert::assertNotContainsInputField('email_goodie', $responseHTML);
        FormFieldAssert::assertNotContainsSelectField('tshirt_size', $responseHTML);
        FormFieldAssert::assertNotContainsInputField('planned_arrival_date', $responseHTML);
        FormFieldAssert::assertNotContainsInputField('mobile_show', $responseHTML);
        FormFieldAssert::assertNotContainsInputField('dect', $responseHTML);
    }

    /**
     * Renders the registration page with a maximum fields config.
     * Asserts that all fields are there.
     *
     * @covers \Volunteersystem\Controllers\RegistrationController
     */
    public function testViewMaximumConfig(): void
    {
        SignUpConfig::setMaximumConfig($this->config);
        $response = $this->subject->view();

        $this->assertSame(200, $response->getStatusCode());
        $responseHTML = $response->getBody()->__toString();

        // assert the expected fields are there
        FormFieldAssert::assertContainsInputField('pronoun', $responseHTML);
        FormFieldAssert::assertContainsInputField('username', $responseHTML);
        FormFieldAssert::assertContainsInputField('email', $responseHTML);
        FormFieldAssert::assertContainsInputField('mobile', $responseHTML);
        FormFieldAssert::assertContainsInputField('password', $responseHTML);
        FormFieldAssert::assertContainsInputField('password_confirmation', $responseHTML);
        FormFieldAssert::assertContainsInputField('firstname', $responseHTML);
        FormFieldAssert::assertContainsInputField('lastname', $responseHTML);
        FormFieldAssert::assertContainsInputField('email_goodie', $responseHTML);
        FormFieldAssert::assertContainsSelectField('tshirt_size', $responseHTML);
        FormFieldAssert::assertContainsInputField('planned_arrival_date', $responseHTML);
        FormFieldAssert::assertContainsInputField('mobile_show', $responseHTML);
        FormFieldAssert::assertContainsInputField('dect', $responseHTML);
    }

    /**
     * @covers \Volunteersystem\Controllers\RegistrationController
     */
    public function testViewVolunteerTypesOAuthPreselection(): void
    {
        SignUpConfig::setMinimumConfig($this->config);
        $volunteerTypes = $this->createVolunteerTypes();
        $this->session->set('oauth2_connect_provider', 'test_oauth_provider');
        $this->session->set('oauth2_groups', [$volunteerTypes[1]->name]);
        $this->oauth
            ->method('getSsoTeams')
            ->with('test_oauth_provider')
            ->willReturn(
                [
                    $volunteerTypes[1]->name => ['id' => $volunteerTypes[1]->id],
                    $volunteerTypes[2]->name => ['id' => $volunteerTypes[2]->id],
                ],
            );

        $response = $this->subject->view();

        $this->assertSame(200, $response->getStatusCode());
        $responseHTML = $response->getBody()->__toString();

        // assert that the unrestricted volunteer type is there and checked
        FormFieldAssert::assertContainsCheckedCheckbox('volunteer_types_' . $volunteerTypes[0]->id, $responseHTML);

        // assert that the first restricted volunteer type from oauth is there and checked
        FormFieldAssert::assertContainsCheckedCheckbox('volunteer_types_' . $volunteerTypes[1]->id, $responseHTML);

        // assert that the second restricted volunteer type not in oauth is there and not checked
        FormFieldAssert::assertContainsUncheckedCheckbox('volunteer_types_' . $volunteerTypes[2]->id, $responseHTML);

        // assert that the volunteer type with "hide_register" = true is not there
        FormFieldAssert::assertNotContainsInputField('volunteer_types_' . $volunteerTypes[3]->id, $responseHTML);
    }

    /**
     * @covers \Volunteersystem\Controllers\RegistrationController
     */
    public function testViewVolunteerTypesPreselection(): void
    {
        $volunteerTypes = $this->createVolunteerTypes();

        SignUpConfig::setMinimumConfig($this->config);
        $response = $this->subject->view();

        $this->assertSame(200, $response->getStatusCode());
        $responseHTML = $response->getBody()->__toString();

        // assert that the unrestricted volunteer type is there and checked
        FormFieldAssert::assertContainsCheckedCheckbox('volunteer_types_' . $volunteerTypes[0]->id, $responseHTML);

        // assert that restricted volunteer type are there and not checked
        FormFieldAssert::assertContainsUncheckedCheckbox('volunteer_types_' . $volunteerTypes[1]->id, $responseHTML);
        FormFieldAssert::assertContainsUncheckedCheckbox('volunteer_types_' . $volunteerTypes[2]->id, $responseHTML);

        // assert that the volunteer type with "hide_register" = true is not there
        FormFieldAssert::assertNotContainsInputField('volunteer_types_' . $volunteerTypes[3]->id, $responseHTML);
    }

    /**
     * Asserts that values are prefilled after submit
     *
     * @covers \Volunteersystem\Controllers\RegistrationController
     */
    public function testViewValuesAfterSubmit(): void
    {
        $volunteerTypes = $this->createVolunteerTypes();

        // fake submit and set form-data in session
        $this->session->set('form-data-register-submit', '1');
        $this->session->set('form-data-volunteer_types_' . $volunteerTypes[1]->id, '1');

        SignUpConfig::setMinimumConfig($this->config);
        $response = $this->subject->view();

        $this->assertSame(200, $response->getStatusCode());
        $responseHTML = $response->getBody()->__toString();

        // assert that the unrestricted volunteer type is not checked
        FormFieldAssert::assertContainsUncheckedCheckbox('volunteer_types_' . $volunteerTypes[0]->id, $responseHTML);

        // assert that the restricted volunteer type is checked
        FormFieldAssert::assertContainsCheckedCheckbox('volunteer_types_' . $volunteerTypes[1]->id, $responseHTML);
    }

    /**
     * Creates three volunteer types:
     * - unrestricted
     * - restricted
     * - unrestricted, hidden on registration
     *
     * @return Array<VolunteerType>
     */
    private function createVolunteerTypes(): array
    {
        $volunteerType1 = VolunteerType::create([
            'name' => 'Test volunteer type 1',
            'restricted' => false,
        ]);

        $volunteerType2 = VolunteerType::create([
            'name' => 'Test volunteer type 2',
            'restricted' => true,
        ]);

        $volunteerType3 = VolunteerType::create([
            'name' => 'Test volunteer type 3',
            'restricted' => true,
        ]);

        $volunteerType4 = VolunteerType::create([
            'name' => 'Test volunteer type 4',
            'hide_register' => true,
            'restricted' => false,
        ]);

        $this->modelsToBeDeleted[] = $volunteerType1;
        $this->modelsToBeDeleted[] = $volunteerType2;
        $this->modelsToBeDeleted[] = $volunteerType3;
        $this->modelsToBeDeleted[] = $volunteerType4;
        return [$volunteerType1, $volunteerType2, $volunteerType3, $volunteerType4];
    }

    private function deleteModels(): void
    {
        foreach ($this->modelsToBeDeleted as $modelToBeDeleted) {
            $modelToBeDeleted->delete();
        }
    }
}
