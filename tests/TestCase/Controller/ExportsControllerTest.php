<?php
namespace App\Test\TestCase\Controller;

use App\Test\TestCase\Controller\TatoebaControllerTestTrait;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\Constraint\Response\HeaderNotSet;
use Cake\TestSuite\IntegrationTestCase;
use Cake\Filesystem\Folder;
use Cake\Filesystem\File;

class ExportsControllerTest extends IntegrationTestCase
{
    use TatoebaControllerTestTrait;

    public $fixtures = [
        'app.Exports',
        'app.SentencesLists',
        'app.Users',
        'app.UsersLanguages',
        'app.PrivateMessages',
        'app.QueuedJobs',
    ];

    private $testExportDir = TMP.'export_tests'.DS;

    public function setUp() {
        parent::setUp();

        $folder = new Folder($this->testExportDir);
        $folder->delete();
        if (!$folder->create($this->testExportDir)) {
            die("Couldn't create test directory '{$this->testExportDir}'");
        }
    }

    public function tearDown() {
        $folder = new Folder($this->testExportDir);
        $folder->delete();
        parent::tearDown();
    }

    public function accessesProvider()
    {
        return [
            [ '/eng/exports/index', null, '/eng/users/login?redirect=%2Feng%2Fexports%2Findex' ],
            [ '/eng/exports/index', 'contributor', true ],
            [ '/eng/exports/index', 'advanced_contributor', true ],
            [ '/eng/exports/index', 'corpus_maintainer', true ],
            [ '/eng/exports/index', 'admin', true ],
            [ '/eng/exports/download/1', null, false ],
            [ '/eng/exports/download/1', 'contributor', false ],
            [ '/eng/exports/download/9999999', 'kazuki', 404 ],
        ];
    }

    /**
     * @dataProvider accessesProvider
     */
    public function testExportsControllerAccess($url, $user, $response)
    {
        $this->assertAccessUrlAs($url, $user, $response);
    }

    public function ajaxAccessesProvider()
    {
        return [
            [ '/eng/exports/list', null, false ],
            [ '/eng/exports/list', 'contributor', true ],
            [ '/eng/exports/list', 'advanced_contributor', true ],
            [ '/eng/exports/list', 'corpus_maintainer', true ],
            [ '/eng/exports/list', 'admin', true ],
        ];
    }

    /**
     * @dataProvider ajaxAccessesProvider
     */
    public function testExportsControllerAjaxAccess($url, $user, $response)
    {
        $this->assertAjaxAccessUrlAs($url, $user, $response);
    }

    public function assertNoHeader($header, $message = '')
    {
        $verboseMessage = $this->extractVerboseMessage($message);
        $this->assertThat(null, new HeaderNotSet($this->_response, $header), $verboseMessage);
    }

    private function exportsAdd()
    {
        $this->ajaxPost('/eng/exports/add', [
            'type' => 'list',
            'list_id' => 2,
            'fields' => ['id', 'lang', 'text']
        ]);
    }

    public function testAdd_asMember()
    {
        $this->logInAs('contributor');

        $this->exportsAdd();

        $this->assertResponseOk();
        $this->assertContentType('application/json');
    }

    public function testAdd_asGuest_success()
    {
        $this->enableCsrfToken();
        $this->exportsAdd();

        $this->assertResponseOk();
        $this->assertContentType('application/json');
    }

    public function testAdd_asGuest_failsWithoutCsrfToken()
    {
        $this->exportsAdd();
        $this->assertResponseError();
    }

    private function createDownloadFile($filename)
    {
        $contents = "some zipped content";
        $file = new File($this->testExportDir.$filename, true);
        $file->write($contents);
        $file->close();

        return strlen($contents);
    }

    private function assertFileDownload($filename, $filesize)
    {
        $this->assertResponseOk();
        $this->assertContentType('application/zip');
        $this->assertHeader('Content-Length', (string)$filesize);
        $this->assertHeader('Accept-Ranges', 'bytes');
        $this->assertNoHeader('Content-Disposition');
        $this->assertHeader('X-Accel-Redirect', "/export_tests/$filename");
        $this->assertResponseEquals('');
    }

    public function testDownload_guestExport_asGuest()
    {
        $filesize = $this->createDownloadFile('kazuki_sentences.zip');
        $this->get("/eng/exports/download/4/A pretty filename.zip");
        $this->assertFileDownload('kazuki_sentences.zip', $filesize);
    }

    public function testDownload_membersExport_asGuest()
    {
        $this->createDownloadFile('kazuki_sentences.zip');
        $this->get("/eng/exports/download/1/A pretty filename.zip");
        $this->assertResponseCode(403);
    }

    public function testDownload_ownExport_asMember()
    {
        $this->logInAs('kazuki');
        $filesize = $this->createDownloadFile('kazuki_sentences.zip');
        $this->get("/eng/exports/download/1/A pretty filename.zip");
        $this->assertFileDownload('kazuki_sentences.zip', $filesize);
    }

    public function testDownload_guestExport_asMember()
    {
        $this->logInAs('kazuki');
        $filesize = $this->createDownloadFile('kazuki_sentences.zip');
        $this->get("/eng/exports/download/4/A pretty filename.zip");
        $this->assertResponseCode(403);
    }

    public function testDownload_cannotDownloadUntilReady()
    {
        $this->logInAs('kazuki');

        $export = TableRegistry::get('Exports')->get(2);
        $file = new File($export->filename, true);
        $file->close();

        $this->get("/eng/exports/download/2");

        $this->assertResponseCode(404);
        $this->assertResponseNotContains($export->filename);
    }
}
