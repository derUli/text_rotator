<?php

declare(strict_types=1);

include_once dirname(__FILE__) . "/../TextRotatorBaseTest.php";

use Spatie\Snapshots\MatchesSnapshots;
use UliCMS\Helpers\TestHelper;

class TextRotatorControllerTest extends TextRotatorBaseTest
{
    use MatchesSnapshots;

    private $testUser;
    private $testGroup;

    protected function setUp(): void
    {
        parent::setUp();

        $_SESSION["language"] = "en";
        $_GET = [
            "slug" => get_frontpage()
        ];
        @session_start();
        $group = new Group();
        $group->setName("test-group");
        $group->addPermission("text_rotator_edit", true);
        $group->save();

        $this->testGroup = $group;

        $user = new User();
        $user->setUsername("test-" . uniqid());
        $user->setLastname("Doe");
        $user->setFirstName("Johne");
        $user->setPassword(uniqid());
        $user->setGroup($group);
        $user->save();

        $this->testUser = $user;
    }

    protected function tearDown(): void
    {
        @session_destroy();
        parent::tearDown();
        $_SESSION = [];
        $_GET = [];
    }

    private function createTestData()
    {
        for ($i = 0; $i <= 3; $i++) {
            $text = new RotatingText();
            $text->setAnimation("great-animation-{$i}");
            $text->setSeparator("|");
            $text->setSpeed(2500);
            $text->setWords("Linux|Apache|PHP|MySQL");
            $text->save();
        }
    }

    public function testGetAnimationItems()
    {
        $controller = new TextRotatorController();
        $items = $controller->getAnimationItems();
        $this->assertTrue(is_array($items));
        $this->assertCount(37, $items);
    }

    public function testSettingsNoEditRight()
    {
        unset($_SESSION["login_id"]);

        $this->createTestData();
        $count = count(RotatingText::getAll());
        $controller = new TextRotatorController();
        $html = $controller->settings();
        $this->assertStringContainsString("<table", $html);
        $this->assertStringContainsString('<div class="scroll"', $html);
        $this->assertEquals($count + 1, substr_count($html, '<tr>'));
        $this->assertEquals(3, substr_count($html, '</th>'));
        $this->assertEquals($count * 3, substr_count($html, '<td'));
        $this->assertStringNotContainsStringIgnoringCase('<i class="fa fa-plus"></i> New', $html);
    }

    public function testSettingsWithEditRights()
    {
        $_SESSION["login_id"] = $this->testUser->getId();
        $this->createTestData();
        $count = count(RotatingText::getAll());
        $controller = new TextRotatorController();
        $html = $controller->settings();
        $this->assertStringContainsString("<table", $html);
        $this->assertStringContainsString('<div class="scroll"', $html);
        $this->assertEquals($count + 1, substr_count($html, '<tr>'));
        $this->assertEquals(5, substr_count($html, '</th>'));
        $this->assertEquals($count * 5, substr_count($html, '<td'));
        $this->assertStringContainsStringIgnoringCase('<i class="fa fa-plus"></i> New', $html);
    }

    private function setUpTestPage()
    {
        $rotatingText1 = new RotatingText();
        $rotatingText1->setWords("Foo, Bar");
        $rotatingText1->setSeparator(",");
        $rotatingText1->setSpeed(2500);
        $rotatingText1->setAnimation("random");
        $rotatingText1->save();

        $rotatingText2 = new RotatingText();
        $rotatingText2->setWords("Hello | World");
        $rotatingText2->setSeparator("|");
        $rotatingText2->setSpeed(4000);
        $rotatingText2->setAnimation("wobble");
        $rotatingText2->save();

        $manager = new UserManager();
        $users = $manager->getAllUsers();
        $user = $users[0];
        $user_id = $user->getId();

        $page = new Page();
        $page->title = "Test Page " . time();
        $page->slug = "test-page-" . time();
        $page->language = "en";
        $page->menu = "not_in_menu";
        $page->content = "Foo {$rotatingText1->getShortcode()}"
                . "Bar{$rotatingText2->getShortcode()}";

        $user_id = $user->getId();
        $groups = Group::getAll();
        $group = $groups[0];
        $group_id = $group->getId();

        $page->author_id = $user_id;
        $page->group_id = $group_id;
        $page->comments_enabled = 1;

        $page->save();

        $_GET["slug"] = $page->slug;
        $_SESSION["languge"] = $page->language;

        return $page;
    }

    public function testGetSettingsHeadline()
    {
        $controller = new TextRotatorController();
        $this->assertMatchesTextSnapshot($controller->getSettingsHeadline());
    }

    public function testSettings()
    {
        $controller = new TextRotatorController();
        $this->assertMatchesHtmlSnapshot($controller->settings());
    }

    public function testEnqueueFrontendStylesheetsReturnsEmpty()
    {
        $html = TestHelper::getOutput(function () {
            $controller = new TextRotatorController();
            $controller->enqueueFrontendStylesheets();
            combinedStylesheetHtml();
        });

        $this->assertEmpty($html);
    }

    public function testEnqueueFrontendStylesheetsReturnsHtml()
    {
        $this->setUpTestPage();
        $html = TestHelper::getOutput(function () {
            $controller = new TextRotatorController();
            $controller->enqueueFrontendStylesheets();
            combinedStylesheetHtml();
        });
        $this->assertStringContainsString(
            '<link rel="stylesheet" href=',
            $html
        );
    }

    public function testEnqueueFrontendFooterScriptsReturnsHtml()
    {
        $this->setUpTestPage();

        $html = TestHelper::getOutput(function () {
            $controller = new TextRotatorController();
            $controller->enqueueFrontendFooterScripts();
            combinedScriptHtml();
        });
        $this->assertStringContainsString(
            '<script',
            $html
        );
    }

    public function testAdminHead()
    {
        $html = TestHelper::getOutput(function () {
            $controller = new TextRotatorController();
            $controller->adminHead();
        });

        $this->assertStringContainsString(
            '<link rel="stylesheet" href=',
            $html
        );
    }

    public function testPreview()
    {
        $controller = new TextRotatorController();
        $html = $controller->_preview(
            "Foo, Bar, Hello, World",
            ",",
            30000,
            "wobble"
        );
        $this->assertMatchesHtmlSnapshot($html);
    }

    public function testBeforeContentFilter()
    {
        $page = $this->setUpTestPage();

        $controller = new TextRotatorController();
        $html = $controller->beforeContentFilter($page->content);

        $this->assertMatchesHtmlSnapshot($html);
    }

    public function testDeletePostReturnsTrue()
    {
        $textRotator = new RotatingText();
        $textRotator->setAnimation("great-animation-{$i}");
        $textRotator->setSeparator("|");
        $textRotator->setSpeed(2500);
        $textRotator->setWords("Linux|Apache|PHP|MySQL");
        $textRotator->save();

        $controller = new TextRotatorController();
        $this->assertTrue(
            $controller->_deletePost(
                $textRotator->getID()
            )
        );
    }

    public function testDeletePostReturnsFalse()
    {
        $controller = new TextRotatorController();
        $this->assertFalse($controller->_deletePost(PHP_INT_MAX));
    }

    public function testSavePost()
    {
        $textRotator = new RotatingText();
        $textRotator->setAnimation("great-animation-{$i}");
        $textRotator->setSeparator("|");
        $textRotator->setSpeed(2500);
        $textRotator->setWords("Linux|Apache|PHP|MySQL");
        $textRotator->save();
        
        $id = $textRotator->getID();

        $controller = new TextRotatorController();
        $success = $controller->_savePost(
            "Foo, Bar",
            ", ",
            4000,
            "random",
            $id
        );
        
        $this->assertTrue($success);
    }
}
