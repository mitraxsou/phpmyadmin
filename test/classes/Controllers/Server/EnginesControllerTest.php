<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Server;

use PhpMyAdmin\Controllers\Server\EnginesController;
use PhpMyAdmin\Html\MySQLDocumentation;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\StorageEngine;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;

use function __;
use function htmlspecialchars;

/**
 * @covers \PhpMyAdmin\Controllers\Server\EnginesController
 */
class EnginesControllerTest extends AbstractTestCase
{
    /**
     * Prepares environment for the test.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['text_dir'] = 'ltr';
        parent::setGlobalConfig();
        parent::setTheme();

        $GLOBALS['server'] = 1;
        $GLOBALS['db'] = 'db';
        $GLOBALS['table'] = 'table';
        $GLOBALS['PMA_PHP_SELF'] = 'index.php';
        $GLOBALS['cfg']['Server']['DisableIS'] = false;
    }

    public function testIndex(): void
    {
        global $dbi;

        $response = new ResponseRenderer();

        $controller = new EnginesController($response, new Template(), $dbi);

        $this->dummyDbi->addSelectDb('mysql');
        $controller->index();
        $this->assertAllSelectsConsumed();

        $actual = $response->getHTMLResult();

        $this->assertStringContainsString(
            '<th scope="col">Storage Engine</th>',
            $actual
        );
        $this->assertStringContainsString(
            '<th scope="col">Description</th>',
            $actual
        );

        $this->assertStringContainsString(
            '<td>Federated MySQL storage engine</td>',
            $actual
        );
        $this->assertStringContainsString(
            'FEDERATED',
            $actual
        );
        $this->assertStringContainsString(
            'index.php?route=/server/engines/FEDERATED',
            $actual
        );

        $this->assertStringContainsString(
            '<td>dummy comment</td>',
            $actual
        );
        $this->assertStringContainsString(
            'dummy',
            $actual
        );
        $this->assertStringContainsString(
            'index.php?route=/server/engines/dummy',
            $actual
        );
    }

    public function testShow(): void
    {
        global $dbi;

        $response = new ResponseRenderer();

        $controller = new EnginesController($response, new Template(), $dbi);

        $request = $this->createMock(ServerRequest::class);

        $this->dummyDbi->addSelectDb('mysql');
        $controller->show($request, [
            'engine' => 'Pbxt',
            'page' => 'page',
        ]);
        $this->assertAllSelectsConsumed();
        $actual = $response->getHTMLResult();

        $enginePlugin = StorageEngine::getEngine('Pbxt');

        $this->assertStringContainsString(
            htmlspecialchars($enginePlugin->getTitle()),
            $actual
        );

        $this->assertStringContainsString(
            MySQLDocumentation::show($enginePlugin->getMysqlHelpPage()),
            $actual
        );

        $this->assertStringContainsString(
            htmlspecialchars($enginePlugin->getComment()),
            $actual
        );

        $this->assertStringContainsString(
            __('Variables'),
            $actual
        );
        $this->assertStringContainsString(
            'index.php?route=/server/engines/Pbxt/Documentation',
            $actual
        );
        $this->assertStringContainsString(
            $enginePlugin->getSupportInformationMessage(),
            $actual
        );
        $this->assertStringContainsString(
            'There is no detailed status information available for this '
            . 'storage engine.',
            $actual
        );
    }
}
