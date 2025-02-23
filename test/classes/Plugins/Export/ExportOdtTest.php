<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Plugins\Export;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\FieldMetadata;
use PhpMyAdmin\Plugins\Export\ExportOdt;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyMainGroup;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyRootGroup;
use PhpMyAdmin\Properties\Options\Items\BoolPropertyItem;
use PhpMyAdmin\Properties\Options\Items\RadioPropertyItem;
use PhpMyAdmin\Properties\Options\Items\TextPropertyItem;
use PhpMyAdmin\Properties\Plugins\ExportPluginProperties;
use PhpMyAdmin\Relation;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Version;
use ReflectionMethod;
use stdClass;

use function __;
use function array_shift;

use const MYSQLI_BLOB_FLAG;
use const MYSQLI_NUM_FLAG;
use const MYSQLI_TYPE_BLOB;
use const MYSQLI_TYPE_DECIMAL;
use const MYSQLI_TYPE_STRING;

/**
 * @covers \PhpMyAdmin\Plugins\Export\ExportOdt
 * @requires extension zip
 * @group medium
 */
class ExportOdtTest extends AbstractTestCase
{
    /** @var ExportOdt */
    protected $object;

    /**
     * Configures global environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        parent::loadDefaultConfig();
        $GLOBALS['server'] = 0;
        $GLOBALS['output_kanji_conversion'] = false;
        $GLOBALS['output_charset_conversion'] = false;
        $GLOBALS['buffer_needed'] = false;
        $GLOBALS['asfile'] = true;
        $GLOBALS['save_on_server'] = false;
        $GLOBALS['plugin_param'] = [];
        $GLOBALS['plugin_param']['export_type'] = 'table';
        $GLOBALS['plugin_param']['single_table'] = false;
        $GLOBALS['cfgRelation']['relation'] = true;
        $GLOBALS['cfg']['Server']['DisableIS'] = true;
        $this->object = new ExportOdt();
    }

    /**
     * tearDown for test cases
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        unset($this->object);
    }

    public function testSetProperties(): void
    {
        $GLOBALS['plugin_param']['export_type'] = '';
        $GLOBALS['plugin_param']['single_table'] = false;
        $GLOBALS['cfgRelation']['mimework'] = true;

        $method = new ReflectionMethod(ExportOdt::class, 'setProperties');
        $method->setAccessible(true);
        $properties = $method->invoke($this->object, null);

        $this->assertInstanceOf(
            ExportPluginProperties::class,
            $properties
        );

        $this->assertEquals(
            'OpenDocument Text',
            $properties->getText()
        );

        $this->assertEquals(
            'odt',
            $properties->getExtension()
        );

        $this->assertEquals(
            'application/vnd.oasis.opendocument.text',
            $properties->getMimeType()
        );

        $this->assertEquals(
            'Options',
            $properties->getOptionsText()
        );

        $this->assertTrue(
            $properties->getForceFile()
        );

        $options = $properties->getOptions();

        $this->assertInstanceOf(
            OptionsPropertyRootGroup::class,
            $options
        );

        $this->assertEquals(
            'Format Specific Options',
            $options->getName()
        );

        $generalOptionsArray = $options->getProperties();

        $generalOptions = array_shift($generalOptionsArray);

        $this->assertInstanceOf(
            OptionsPropertyMainGroup::class,
            $generalOptions
        );

        $this->assertEquals(
            'general_opts',
            $generalOptions->getName()
        );

        $this->assertEquals(
            'Dump table',
            $generalOptions->getText()
        );

        $generalProperties = $generalOptions->getProperties();

        $property = array_shift($generalProperties);

        $this->assertInstanceOf(
            RadioPropertyItem::class,
            $property
        );

        $this->assertEquals(
            'structure_or_data',
            $property->getName()
        );

        $this->assertEquals(
            [
                'structure' => __('structure'),
                'data' => __('data'),
                'structure_and_data' => __('structure and data'),
            ],
            $property->getValues()
        );

        $generalOptions = array_shift($generalOptionsArray);

        $this->assertInstanceOf(
            OptionsPropertyMainGroup::class,
            $generalOptions
        );

        $this->assertEquals(
            'structure',
            $generalOptions->getName()
        );

        $this->assertEquals(
            'Object creation options',
            $generalOptions->getText()
        );

        $this->assertEquals(
            'data',
            $generalOptions->getForce()
        );

        $generalProperties = $generalOptions->getProperties();

        $property = array_shift($generalProperties);

        $this->assertInstanceOf(
            BoolPropertyItem::class,
            $property
        );

        $this->assertEquals(
            'relation',
            $property->getName()
        );

        $this->assertEquals(
            'Display foreign key relationships',
            $property->getText()
        );

        $property = array_shift($generalProperties);

        $this->assertInstanceOf(
            BoolPropertyItem::class,
            $property
        );

        $this->assertEquals(
            'comments',
            $property->getName()
        );

        $this->assertEquals(
            'Display comments',
            $property->getText()
        );

        $property = array_shift($generalProperties);

        $this->assertInstanceOf(
            BoolPropertyItem::class,
            $property
        );

        $this->assertEquals(
            'mime',
            $property->getName()
        );

        $this->assertEquals(
            'Display media types',
            $property->getText()
        );

        // hide structure
        $generalOptions = array_shift($generalOptionsArray);

        $this->assertInstanceOf(
            OptionsPropertyMainGroup::class,
            $generalOptions
        );

        $this->assertEquals(
            'data',
            $generalOptions->getName()
        );

        $this->assertEquals(
            'Data dump options',
            $generalOptions->getText()
        );

        $this->assertEquals(
            'structure',
            $generalOptions->getForce()
        );

        $generalProperties = $generalOptions->getProperties();

        $property = array_shift($generalProperties);

        $this->assertInstanceOf(
            BoolPropertyItem::class,
            $property
        );

        $this->assertEquals(
            'columns',
            $property->getName()
        );

        $this->assertEquals(
            'Put columns names in the first row',
            $property->getText()
        );

        $property = array_shift($generalProperties);

        $this->assertInstanceOf(
            TextPropertyItem::class,
            $property
        );

        $this->assertEquals(
            'null',
            $property->getName()
        );

        $this->assertEquals(
            'Replace NULL with:',
            $property->getText()
        );

        // case 2
        $GLOBALS['plugin_param']['export_type'] = 'table';
        $GLOBALS['plugin_param']['single_table'] = false;

        $method->invoke($this->object, null);

        $generalOptionsArray = $options->getProperties();

        $this->assertCount(
            3,
            $generalOptionsArray
        );
    }

    public function testExportHeader(): void
    {
        $this->assertTrue(
            $this->object->exportHeader()
        );

        $this->assertStringContainsString(
            '<office:document-content',
            $GLOBALS['odt_buffer']
        );
        $this->assertStringContainsString(
            'office:version',
            $GLOBALS['odt_buffer']
        );
    }

    public function testExportFooter(): void
    {
        $GLOBALS['odt_buffer'] = 'header';

        $this->expectOutputRegex('/^504b.*636f6e74656e742e786d6c/');
        $this->setOutputCallback('bin2hex');

        $this->assertTrue(
            $this->object->exportFooter()
        );

        $this->assertStringContainsString(
            'header',
            $GLOBALS['odt_buffer']
        );

        $this->assertStringContainsString(
            '</office:text></office:body></office:document-content>',
            $GLOBALS['odt_buffer']
        );
    }

    public function testExportDBHeader(): void
    {
        $GLOBALS['odt_buffer'] = 'header';

        $this->assertTrue(
            $this->object->exportDBHeader('d&b')
        );

        $this->assertStringContainsString(
            'header',
            $GLOBALS['odt_buffer']
        );

        $this->assertStringContainsString(
            'Database d&amp;b</text:h>',
            $GLOBALS['odt_buffer']
        );
    }

    public function testExportDBFooter(): void
    {
        $this->assertTrue(
            $this->object->exportDBFooter('testDB')
        );
    }

    public function testExportDBCreate(): void
    {
        $this->assertTrue(
            $this->object->exportDBCreate('testDB', 'database')
        );
    }

    public function testExportData(): void
    {
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $flags = [];
        $a = new stdClass();
        $flags[] = new FieldMetadata(-1, 0, $a);

        $a = new stdClass();
        $a->charsetnr = 63;
        $flags[] = new FieldMetadata(MYSQLI_TYPE_BLOB, MYSQLI_BLOB_FLAG, $a);

        $flags[] = new FieldMetadata(MYSQLI_TYPE_DECIMAL, MYSQLI_NUM_FLAG, (object) []);

        $flags[] = new FieldMetadata(MYSQLI_TYPE_STRING, 0, (object) []);

        $dbi->expects($this->once())
            ->method('getFieldsMeta')
            ->with(true)
            ->will($this->returnValue($flags));

        $dbi->expects($this->once())
            ->method('query')
            ->with('SELECT', DatabaseInterface::CONNECT_USER, DatabaseInterface::QUERY_UNBUFFERED)
            ->will($this->returnValue(true));

        $dbi->expects($this->once())
            ->method('numFields')
            ->with(true)
            ->will($this->returnValue(4));

        $dbi->expects($this->exactly(2))
            ->method('fetchRow')
            ->willReturnOnConsecutiveCalls(
                [
                    null,
                    'a<b',
                    'a>b',
                    'a&b',
                ],
                null
            );

        $GLOBALS['dbi'] = $dbi;
        $GLOBALS['what'] = 'foo';
        $GLOBALS['foo_null'] = '&';
        unset($GLOBALS['foo_columns']);

        $this->assertTrue(
            $this->object->exportData(
                'db',
                'ta<ble',
                "\n",
                'example.com',
                'SELECT'
            )
        );

        $this->assertEquals(
            '<text:h text:outline-level="2" text:style-name="Heading_2" ' .
            'text:is-list-header="true">Dumping data for table ta&lt;ble</text:h>' .
            '<table:table table:name="ta&lt;ble_structure"><table:table-column ' .
            'table:number-columns-repeated="4"/><table:table-row>' .
            '<table:table-cell office:value-type="string"><text:p>&amp;</text:p>' .
            '</table:table-cell><table:table-cell office:value-type="string">' .
            '<text:p></text:p></table:table-cell><table:table-cell ' .
            'office:value-type="float" office:value="a>b" ><text:p>a&gt;b</text:p>' .
            '</table:table-cell><table:table-cell office:value-type="string">' .
            '<text:p>a&amp;b</text:p></table:table-cell></table:table-row>' .
            '</table:table>',
            $GLOBALS['odt_buffer']
        );
    }

    public function testExportDataWithFieldNames(): void
    {
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $flags = [];
        $a = new stdClass();
        $a->name = 'fna\"me';
        $a->length = 20;
        $flags[] = new FieldMetadata(MYSQLI_TYPE_STRING, 0, $a);
        $b = new stdClass();
        $b->name = 'fnam/<e2';
        $b->length = 20;
        $flags[] = new FieldMetadata(MYSQLI_TYPE_STRING, 0, $b);

        $dbi->expects($this->once())
            ->method('getFieldsMeta')
            ->with(true)
            ->will($this->returnValue($flags));

        $dbi->expects($this->once())
            ->method('query')
            ->with('SELECT', DatabaseInterface::CONNECT_USER, DatabaseInterface::QUERY_UNBUFFERED)
            ->will($this->returnValue(true));

        $dbi->expects($this->once())
            ->method('numFields')
            ->with(true)
            ->will($this->returnValue(2));

        $dbi->expects($this->exactly(2))
            ->method('fieldName')
            ->willReturnOnConsecutiveCalls(
                'fna\"me',
                'fnam/<e2'
            );

        $dbi->expects($this->exactly(1))
            ->method('fetchRow')
            ->with(true)
            ->will(
                $this->returnValue(
                    null
                )
            );

        $GLOBALS['dbi'] = $dbi;
        $GLOBALS['what'] = 'foo';
        $GLOBALS['foo_null'] = '&';
        $GLOBALS['foo_columns'] = true;

        $this->assertTrue(
            $this->object->exportData(
                'db',
                'table',
                "\n",
                'example.com',
                'SELECT'
            )
        );

        $this->assertEquals(
            '<text:h text:outline-level="2" text:style-name="Heading_2" text:' .
            'is-list-header="true">Dumping data for table table</text:h><table:' .
            'table table:name="table_structure"><table:table-column table:number-' .
            'columns-repeated="2"/><table:table-row><table:table-cell office:' .
            'value-type="string"><text:p>fna&quot;me</text:p></table:table-cell>' .
            '<table:table-cell office:value-type="string"><text:p>fnam/&lt;e2' .
            '</text:p></table:table-cell></table:table-row></table:table>',
            $GLOBALS['odt_buffer']
        );

        // with no row count
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $flags = [];

        $dbi->expects($this->once())
            ->method('getFieldsMeta')
            ->with(true)
            ->will($this->returnValue($flags));

        $dbi->expects($this->once())
            ->method('query')
            ->with('SELECT', DatabaseInterface::CONNECT_USER, DatabaseInterface::QUERY_UNBUFFERED)
            ->will($this->returnValue(true));

        $dbi->expects($this->once())
            ->method('numFields')
            ->with(true)
            ->will($this->returnValue(0));

        $dbi->expects($this->once())
            ->method('fetchRow')
            ->with(true)
            ->will(
                $this->returnValue(
                    null
                )
            );

        $GLOBALS['dbi'] = $dbi;
        $GLOBALS['mediawiki_caption'] = true;
        $GLOBALS['mediawiki_headers'] = true;
        $GLOBALS['what'] = 'foo';
        $GLOBALS['foo_null'] = '&';
        $GLOBALS['odt_buffer'] = '';

        $this->assertTrue(
            $this->object->exportData(
                'db',
                'table',
                "\n",
                'example.com',
                'SELECT'
            )
        );

        $this->assertEquals(
            '<text:h text:outline-level="2" text:style-name="Heading_2" ' .
            'text:is-list-header="true">Dumping data for table table</text:h>' .
            '<table:table table:name="table_structure"><table:table-column ' .
            'table:number-columns-repeated="0"/><table:table-row>' .
            '</table:table-row></table:table>',
            $GLOBALS['odt_buffer']
        );
    }

    public function testGetTableDefStandIn(): void
    {
        $this->dummyDbi->addSelectDb('test_db');
        $this->assertSame(
            $this->object->getTableDefStandIn('test_db', 'test_table', "\n"),
            ''
        );
        $this->assertAllSelectsConsumed();

        $this->assertEquals(
            '<table:table table:name="test_table_data">'
            . '<table:table-column table:number-columns-repeated="4"/><table:table-row>'
            . '<table:table-cell office:value-type="string"><text:p>Column</text:p>'
            . '</table:table-cell><table:table-cell office:value-type="string"><text:p>Type</text:p>'
            . '</table:table-cell><table:table-cell office:value-type="string"><text:p>Null</text:p>'
            . '</table:table-cell><table:table-cell office:value-type="string"><text:p>Default</text:p>'
            . '</table:table-cell></table:table-row><table:table-row>'
            . '<table:table-cell office:value-type="string"><text:p>id</text:p></table:table-cell>'
            . '<table:table-cell office:value-type="string"><text:p>int(11)</text:p></table:table-cell>'
            . '<table:table-cell office:value-type="string"><text:p>No</text:p></table:table-cell>'
            . '<table:table-cell office:value-type="string"><text:p>NULL</text:p></table:table-cell>'
            . '</table:table-row><table:table-row>'
            . '<table:table-cell office:value-type="string"><text:p>name</text:p></table:table-cell>'
            . '<table:table-cell office:value-type="string"><text:p>varchar(20)</text:p></table:table-cell>'
            . '<table:table-cell office:value-type="string"><text:p>No</text:p></table:table-cell>'
            . '<table:table-cell office:value-type="string"><text:p>NULL</text:p></table:table-cell>'
            . '</table:table-row><table:table-row>'
            . '<table:table-cell office:value-type="string"><text:p>datetimefield</text:p></table:table-cell>'
            . '<table:table-cell office:value-type="string"><text:p>datetime</text:p></table:table-cell>'
            . '<table:table-cell office:value-type="string"><text:p>No</text:p></table:table-cell>'
            . '<table:table-cell office:value-type="string"><text:p>NULL</text:p></table:table-cell>'
            . '</table:table-row></table:table>',
            $GLOBALS['odt_buffer']
        );
    }

    public function testGetTableDef(): void
    {
        $this->object = $this->getMockBuilder(ExportOdt::class)
            ->onlyMethods(['formatOneColumnDefinition'])
            ->getMock();

        // case 1

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->exactly(2))
            ->method('fetchResult')
            ->willReturnOnConsecutiveCalls(
                [],
                [
                    'fieldname' => [
                        'values' => 'test-',
                        'transformation' => 'testfoo',
                        'mimetype' => 'test<',
                    ],
                ]
            );

        $columns = ['Field' => 'fieldname'];
        $dbi->expects($this->once())
            ->method('getColumns')
            ->with('database', '')
            ->will($this->returnValue([$columns]));

        $dbi->expects($this->any())
            ->method('query')
            ->will($this->returnValue(true));

        $dbi->expects($this->any())
            ->method('numRows')
            ->will($this->returnValue(1));

        $dbi->expects($this->any())
            ->method('fetchAssoc')
            ->will(
                $this->returnValue(
                    [
                        'comment' => ['fieldname' => 'testComment'],
                    ]
                )
            );

        $GLOBALS['dbi'] = $dbi;
        $this->object->relation = new Relation($dbi);

        $this->object->expects($this->exactly(2))
            ->method('formatOneColumnDefinition')
            ->with(['Field' => 'fieldname'])
            ->will($this->returnValue(1));

        $GLOBALS['cfgRelation']['relation'] = true;
        $_SESSION['relation'][0] = [
            'version' => Version::VERSION,
            'relwork' => true,
            'commwork' => true,
            'mimework' => true,
            'db' => 'database',
            'relation' => 'rel',
            'column_info' => 'col',
        ];
        $this->assertTrue(
            $this->object->getTableDef(
                'database',
                '',
                "\n",
                'example.com',
                true,
                true,
                true
            )
        );

        $this->assertStringContainsString(
            '<table:table table:name="_structure"><table:table-column ' .
            'table:number-columns-repeated="6"/>',
            $GLOBALS['odt_buffer']
        );

        $this->assertStringContainsString(
            '<table:table-cell office:value-type="string"><text:p>Comments' .
            '</text:p></table:table-cell>',
            $GLOBALS['odt_buffer']
        );

        $this->assertStringContainsString(
            '<table:table-cell office:value-type="string"><text:p>Media type' .
            '</text:p></table:table-cell>',
            $GLOBALS['odt_buffer']
        );

        $this->assertStringContainsString(
            '</table:table-row>1<table:table-cell office:value-type="string">' .
            '<text:p></text:p></table:table-cell><table:table-cell office:value-' .
            'type="string"><text:p>Test&lt;</text:p></table:table-cell>' .
            '</table:table-row></table:table>',
            $GLOBALS['odt_buffer']
        );

        // case 2

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->exactly(2))
            ->method('fetchResult')
            ->willReturnOnConsecutiveCalls(
                [
                    'fieldname' => [
                        'foreign_table' => 'ftable',
                        'foreign_field' => 'ffield',
                    ],
                ],
                [
                    'field' => [
                        'values' => 'test-',
                        'transformation' => 'testfoo',
                        'mimetype' => 'test<',
                    ],
                ]
            );

        $columns = ['Field' => 'fieldname'];

        $dbi->expects($this->once())
            ->method('getColumns')
            ->with('database', '')
            ->will($this->returnValue([$columns]));

        $dbi->expects($this->any())
            ->method('query')
            ->will($this->returnValue(true));

        $dbi->expects($this->any())
            ->method('numRows')
            ->will($this->returnValue(1));

        $dbi->expects($this->any())
            ->method('fetchAssoc')
            ->will(
                $this->returnValue(
                    [
                        'comment' => ['field' => 'testComment'],
                    ]
                )
            );

        $GLOBALS['dbi'] = $dbi;
        $this->object->relation = new Relation($dbi);
        $GLOBALS['odt_buffer'] = '';
        $GLOBALS['cfgRelation']['relation'] = true;
        $_SESSION['relation'][0] = [
            'version' => Version::VERSION,
            'relwork' => true,
            'commwork' => true,
            'mimework' => true,
            'db' => 'database',
            'relation' => 'rel',
            'column_info' => 'col',
        ];

        $this->assertTrue(
            $this->object->getTableDef(
                'database',
                '',
                "\n",
                'example.com',
                true,
                true,
                true
            )
        );

        $this->assertStringContainsString(
            '<text:p>ftable (ffield)</text:p>',
            $GLOBALS['odt_buffer']
        );
    }

    public function testGetTriggers(): void
    {
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $triggers = [
            [
                'name' => 'tna"me',
                'action_timing' => 'ac>t',
                'event_manipulation' => 'manip&',
                'definition' => 'def',
            ],
        ];

        $dbi->expects($this->once())
            ->method('getTriggers')
            ->with('database', 'ta<ble')
            ->will($this->returnValue($triggers));

        $GLOBALS['dbi'] = $dbi;

        $method = new ReflectionMethod(ExportOdt::class, 'getTriggers');
        $method->setAccessible(true);
        $result = $method->invoke($this->object, 'database', 'ta<ble');

        $this->assertSame($result, $GLOBALS['odt_buffer']);

        $this->assertStringContainsString(
            '<table:table table:name="ta&lt;ble_triggers">',
            $result
        );

        $this->assertStringContainsString(
            '<text:p>tna&quot;me</text:p>',
            $result
        );

        $this->assertStringContainsString(
            '<text:p>ac&gt;t</text:p>',
            $result
        );

        $this->assertStringContainsString(
            '<text:p>manip&amp;</text:p>',
            $result
        );

        $this->assertStringContainsString(
            '<text:p>def</text:p>',
            $result
        );
    }

    public function testExportStructure(): void
    {
        // case 1
        $this->dummyDbi->addSelectDb('test_db');
        $this->assertTrue(
            $this->object->exportStructure(
                'test_db',
                'test_table',
                "\n",
                'localhost',
                'create_table',
                'test'
            )
        );
        $this->assertAllSelectsConsumed();

        $this->assertEquals(
            '<text:h text:outline-level="2" text:style-name="Heading_2" text:is-list-header="true">'
            . 'Table structure for table test_table</text:h><table:table table:name="test_table_structure">'
            . '<table:table-column table:number-columns-repeated="4"/><table:table-row>'
            . '<table:table-cell office:value-type="string"><text:p>Column</text:p></table:table-cell>'
            . '<table:table-cell office:value-type="string"><text:p>Type</text:p></table:table-cell>'
            . '<table:table-cell office:value-type="string"><text:p>Null</text:p></table:table-cell>'
            . '<table:table-cell office:value-type="string"><text:p>Default</text:p></table:table-cell>'
            . '</table:table-row><table:table-row>'
            . '<table:table-cell office:value-type="string"><text:p>id</text:p></table:table-cell>'
            . '<table:table-cell office:value-type="string"><text:p>int(11)</text:p></table:table-cell>'
            . '<table:table-cell office:value-type="string"><text:p>No</text:p></table:table-cell>'
            . '<table:table-cell office:value-type="string"><text:p>NULL</text:p></table:table-cell>'
            . '</table:table-row><table:table-row>'
            . '<table:table-cell office:value-type="string"><text:p>name</text:p></table:table-cell>'
            . '<table:table-cell office:value-type="string"><text:p>varchar(20)</text:p></table:table-cell>'
            . '<table:table-cell office:value-type="string"><text:p>No</text:p></table:table-cell>'
            . '<table:table-cell office:value-type="string"><text:p>NULL</text:p></table:table-cell>'
            . '</table:table-row><table:table-row>'
            . '<table:table-cell office:value-type="string"><text:p>datetimefield</text:p></table:table-cell>'
            . '<table:table-cell office:value-type="string"><text:p>datetime</text:p></table:table-cell>'
            . '<table:table-cell office:value-type="string"><text:p>No</text:p></table:table-cell>'
            . '<table:table-cell office:value-type="string"><text:p>NULL</text:p></table:table-cell>'
            . '</table:table-row></table:table>',
            $GLOBALS['odt_buffer']
        );

        // case 2
        $GLOBALS['odt_buffer'] = '';

        $this->assertTrue(
            $this->object->exportStructure(
                'test_db',
                'test_table',
                "\n",
                'localhost',
                'triggers',
                'test'
            )
        );

        $this->assertEquals(
            '<text:h text:outline-level="2" text:style-name="Heading_2" text:is-list-header="true">'
            . 'Triggers test_table</text:h><table:table table:name="test_table_triggers">'
            . '<table:table-column table:number-columns-repeated="4"/><table:table-row>'
            . '<table:table-cell office:value-type="string"><text:p>Name</text:p></table:table-cell>'
            . '<table:table-cell office:value-type="string"><text:p>Time</text:p></table:table-cell>'
            . '<table:table-cell office:value-type="string"><text:p>Event</text:p></table:table-cell>'
            . '<table:table-cell office:value-type="string"><text:p>Definition</text:p></table:table-cell>'
            . '</table:table-row><table:table-row>'
            . '<table:table-cell office:value-type="string"><text:p>test_trigger</text:p></table:table-cell>'
            . '<table:table-cell office:value-type="string"><text:p>AFTER</text:p></table:table-cell>'
            . '<table:table-cell office:value-type="string"><text:p>INSERT</text:p></table:table-cell>'
            . '<table:table-cell office:value-type="string"><text:p>BEGIN END</text:p></table:table-cell>'
            . '</table:table-row></table:table>',
            $GLOBALS['odt_buffer']
        );

        // case 3
        $GLOBALS['odt_buffer'] = '';

        $this->dummyDbi->addSelectDb('test_db');
        $this->assertTrue(
            $this->object->exportStructure(
                'test_db',
                'test_table',
                "\n",
                'localhost',
                'create_view',
                'test'
            )
        );
        $this->assertAllSelectsConsumed();

        $this->assertEquals(
            '<text:h text:outline-level="2" text:style-name="Heading_2" text:is-list-header="true">'
            . 'Structure for view test_table</text:h><table:table table:name="test_table_structure">'
            . '<table:table-column table:number-columns-repeated="4"/><table:table-row>'
            . '<table:table-cell office:value-type="string"><text:p>Column</text:p></table:table-cell>'
            . '<table:table-cell office:value-type="string"><text:p>Type</text:p></table:table-cell>'
            . '<table:table-cell office:value-type="string"><text:p>Null</text:p></table:table-cell>'
            . '<table:table-cell office:value-type="string"><text:p>Default</text:p></table:table-cell>'
            . '</table:table-row><table:table-row>'
            . '<table:table-cell office:value-type="string"><text:p>id</text:p></table:table-cell>'
            . '<table:table-cell office:value-type="string"><text:p>int(11)</text:p></table:table-cell>'
            . '<table:table-cell office:value-type="string"><text:p>No</text:p></table:table-cell>'
            . '<table:table-cell office:value-type="string"><text:p>NULL</text:p></table:table-cell>'
            . '</table:table-row><table:table-row>'
            . '<table:table-cell office:value-type="string"><text:p>name</text:p></table:table-cell>'
            . '<table:table-cell office:value-type="string"><text:p>varchar(20)</text:p></table:table-cell>'
            . '<table:table-cell office:value-type="string"><text:p>No</text:p></table:table-cell>'
            . '<table:table-cell office:value-type="string"><text:p>NULL</text:p></table:table-cell>'
            . '</table:table-row><table:table-row>'
            . '<table:table-cell office:value-type="string"><text:p>datetimefield</text:p></table:table-cell>'
            . '<table:table-cell office:value-type="string"><text:p>datetime</text:p></table:table-cell>'
            . '<table:table-cell office:value-type="string"><text:p>No</text:p></table:table-cell>'
            . '<table:table-cell office:value-type="string"><text:p>NULL</text:p></table:table-cell>'
            . '</table:table-row></table:table>',
            $GLOBALS['odt_buffer']
        );

        // case 4
        $this->dummyDbi->addSelectDb('test_db');
        $GLOBALS['odt_buffer'] = '';
        $this->assertTrue(
            $this->object->exportStructure(
                'test_db',
                'test_table',
                "\n",
                'localhost',
                'stand_in',
                'test'
            )
        );
        $this->assertAllSelectsConsumed();

        $this->assertEquals(
            '<text:h text:outline-level="2" text:style-name="Heading_2" text:is-list-header="true">'
            . 'Stand-in structure for view test_table</text:h><table:table table:name="test_table_data">'
            . '<table:table-column table:number-columns-repeated="4"/><table:table-row>'
            . '<table:table-cell office:value-type="string"><text:p>Column</text:p></table:table-cell>'
            . '<table:table-cell office:value-type="string"><text:p>Type</text:p></table:table-cell>'
            . '<table:table-cell office:value-type="string"><text:p>Null</text:p></table:table-cell>'
            . '<table:table-cell office:value-type="string"><text:p>Default</text:p></table:table-cell>'
            . '</table:table-row><table:table-row>'
            . '<table:table-cell office:value-type="string"><text:p>id</text:p></table:table-cell>'
            . '<table:table-cell office:value-type="string"><text:p>int(11)</text:p></table:table-cell>'
            . '<table:table-cell office:value-type="string"><text:p>No</text:p></table:table-cell>'
            . '<table:table-cell office:value-type="string"><text:p>NULL</text:p></table:table-cell>'
            . '</table:table-row><table:table-row>'
            . '<table:table-cell office:value-type="string"><text:p>name</text:p></table:table-cell>'
            . '<table:table-cell office:value-type="string"><text:p>varchar(20)</text:p></table:table-cell>'
            . '<table:table-cell office:value-type="string"><text:p>No</text:p></table:table-cell>'
            . '<table:table-cell office:value-type="string"><text:p>NULL</text:p></table:table-cell>'
            . '</table:table-row><table:table-row>'
            . '<table:table-cell office:value-type="string"><text:p>datetimefield</text:p></table:table-cell>'
            . '<table:table-cell office:value-type="string"><text:p>datetime</text:p></table:table-cell>'
            . '<table:table-cell office:value-type="string"><text:p>No</text:p></table:table-cell>'
            . '<table:table-cell office:value-type="string"><text:p>NULL</text:p></table:table-cell>'
            . '</table:table-row></table:table>',
            $GLOBALS['odt_buffer']
        );
    }

    public function testFormatOneColumnDefinition(): void
    {
        $method = new ReflectionMethod(
            ExportOdt::class,
            'formatOneColumnDefinition'
        );
        $method->setAccessible(true);

        $cols = [
            'Null' => 'Yes',
            'Field' => 'field',
            'Key' => 'PRI',
            'Type' => 'set(abc)enum123',
        ];

        $col_alias = 'alias';

        $this->assertEquals(
            '<table:table-row><table:table-cell office:value-type="string">' .
            '<text:p>alias</text:p></table:table-cell><table:table-cell off' .
            'ice:value-type="string"><text:p>set(abc)</text:p></table:table' .
            '-cell><table:table-cell office:value-type="string"><text:p>Yes' .
            '</text:p></table:table-cell><table:table-cell office:value-typ' .
            'e="string"><text:p>NULL</text:p></table:table-cell>',
            $method->invoke($this->object, $cols, $col_alias)
        );

        $cols = [
            'Null' => 'NO',
            'Field' => 'fields',
            'Key' => 'COMP',
            'Type' => '',
            'Default' => 'def',
        ];

        $this->assertEquals(
            '<table:table-row><table:table-cell office:value-type="string">' .
            '<text:p>fields</text:p></table:table-cell><table:table-cell off' .
            'ice:value-type="string"><text:p>&amp;nbsp;</text:p></table:table' .
            '-cell><table:table-cell office:value-type="string"><text:p>No' .
            '</text:p></table:table-cell><table:table-cell office:value-type=' .
            '"string"><text:p>def</text:p></table:table-cell>',
            $method->invoke($this->object, $cols, '')
        );
    }
}
