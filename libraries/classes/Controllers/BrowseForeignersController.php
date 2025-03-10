<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers;

use PhpMyAdmin\BrowseForeigners;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Relation;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;

/**
 * Display selection for relational field values
 */
class BrowseForeignersController extends AbstractController
{
    /** @var BrowseForeigners */
    private $browseForeigners;

    /** @var Relation */
    private $relation;

    /**
     * @param ResponseRenderer $response
     * @param BrowseForeigners $browseForeigners
     * @param Relation         $relation
     */
    public function __construct($response, Template $template, $browseForeigners, $relation)
    {
        parent::__construct($response, $template);
        $this->browseForeigners = $browseForeigners;
        $this->relation = $relation;
    }

    public function __invoke(ServerRequest $request): void
    {
        /** @var string|null $database */
        $database = $request->getParsedBodyParam('db');
        /** @var string|null $table */
        $table = $request->getParsedBodyParam('table');
        /** @var string|null $field */
        $field = $request->getParsedBodyParam('field');
        /** @var string $fieldKey */
        $fieldKey = $request->getParsedBodyParam('fieldkey', '');
        /** @var string $data */
        $data = $request->getParsedBodyParam('data', '');
        /** @var string|null $foreignShowAll */
        $foreignShowAll = $request->getParsedBodyParam('foreign_showAll');
        /** @var string $foreignFilter */
        $foreignFilter = $request->getParsedBodyParam('foreign_filter', '');

        if (! isset($database, $table, $field)) {
            return;
        }

        $this->response->getFooter()->setMinimal();
        $header = $this->response->getHeader();
        $header->disableMenuAndConsole();
        $header->setBodyId('body_browse_foreigners');

        $foreigners = $this->relation->getForeigners(
            $database,
            $table
        );
        $foreignLimit = $this->browseForeigners->getForeignLimit(
            $foreignShowAll
        );
        $foreignData = $this->relation->getForeignData(
            $foreigners,
            $field,
            true,
            $foreignFilter,
            $foreignLimit ?? '',
            true
        );

        $this->response->addHTML($this->browseForeigners->getHtmlForRelationalFieldSelection(
            $database,
            $table,
            $field,
            $foreignData,
            $fieldKey,
            $data
        ));
    }
}
