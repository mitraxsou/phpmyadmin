<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;

final class TableController extends AbstractController
{
    /** @var DatabaseInterface */
    private $dbi;

    /**
     * @param ResponseRenderer  $response
     * @param DatabaseInterface $dbi
     */
    public function __construct($response, Template $template, $dbi)
    {
        parent::__construct($response, $template);
        $this->dbi = $dbi;
    }

    public function __invoke(): void
    {
        if (! isset($_POST['db'])) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON(['message' => Message::error()]);

            return;
        }

        $this->response->addJSON(['tables' => $this->dbi->getTables($_POST['db'])]);
    }
}
