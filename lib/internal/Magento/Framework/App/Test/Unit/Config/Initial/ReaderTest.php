<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Framework\App\Test\Unit\Config\Initial;

use Magento\Framework\Filesystem;

class ReaderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;

    /**
     * @var \Magento\Framework\App\Config\Initial\Reader
     */
    protected $model;

    /**
     * @var \Magento\Framework\Config\FileResolverInterface | \PHPUnit_Framework_MockObject_MockObject
     */
    protected $fileResolverMock;

    /**
     * @var \Magento\Framework\App\Config\Initial\Converter | \PHPUnit_Framework_MockObject_MockObject
     */
    protected $converterMock;

    /**
     * @var string
     */
    protected $filePath;

    /**
     * @var \Magento\Framework\Config\ValidationStateInterface | \PHPUnit_Framework_MockObject_MockObject
     */
    protected $validationStateMock;

    /**
     * @var \Magento\Framework\App\Config\Initial\SchemaLocator | \PHPUnit_Framework_MockObject_MockObject
     */
    protected $schemaLocatorMock;

    protected function setUp()
    {
        $this->objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->filePath = __DIR__ . '/_files/';
        $this->fileResolverMock = $this->getMock('Magento\Framework\Config\FileResolverInterface');
        $this->converterMock = $this->getMock('Magento\Framework\App\Config\Initial\Converter');
        $this->schemaLocatorMock = $this->getMock(
            'Magento\Framework\App\Config\Initial\SchemaLocator',
            [],
            [],
            '',
            false
        );
        $this->validationStateMock = $this->getMock('Magento\Framework\Config\ValidationStateInterface');
    }

    public function testConstructor()
    {
        $this->createModelAndVerifyConstructor();
    }

    /**
     * @covers \Magento\Framework\App\Config\Initial\Reader::read
     */
    public function testReadNoFiles()
    {
        $this->createModelAndVerifyConstructor();
        $this->fileResolverMock->expects($this->at(0))
            ->method('get')
            ->with('config.xml', 'global')
            ->will($this->returnValue([]));

        $this->assertEquals([], $this->model->read());
    }

    /**
     * @covers \Magento\Framework\App\Config\Initial\Reader::read
     */
    public function testReadValidConfig()
    {
        $this->createModelAndVerifyConstructor();
        $testXmlFilesList = [
            file_get_contents($this->filePath . 'initial_config1.xml'),
            file_get_contents($this->filePath . 'initial_config2.xml'),
        ];
        $expectedConfig = ['data' => [], 'metadata' => []];

        $this->fileResolverMock->expects($this->at(0))
            ->method('get')
            ->with('config.xml', 'global')
            ->will($this->returnValue($testXmlFilesList));

        $this->converterMock->expects($this->once())
            ->method('convert')
            ->with($this->anything())
            ->will($this->returnValue($expectedConfig));

        $this->assertEquals($expectedConfig, $this->model->read());
    }

    /**
     * @covers \Magento\Framework\App\Config\Initial\Reader::read
     * @expectedException \Magento\Framework\Exception
     * @expectedExceptionMessageRegExp /Invalid XML in file \w+/
     */
    public function testReadInvalidConfig()
    {
        $this->createModelAndVerifyConstructor();
        $testXmlFilesList = [
            file_get_contents($this->filePath . 'invalid_config.xml'),
            file_get_contents($this->filePath . 'initial_config2.xml'),
        ];
        $expectedConfig = ['data' => [], 'metadata' => []];

        $this->fileResolverMock->expects($this->at(0))
            ->method('get')
            ->with('config.xml', 'global')
            ->will($this->returnValue($testXmlFilesList));

        $this->converterMock->expects($this->never())
            ->method('convert')
            ->with($this->anything())
            ->will($this->returnValue($expectedConfig));

        $this->model->read();
    }

    private function createModelAndVerifyConstructor()
    {
        $this->validationStateMock->expects($this->once())->method('isValidated')->will($this->returnValue(true));
        $schemaFile = $this->filePath . 'config.xsd';
        $this->schemaLocatorMock->expects($this->once())->method('getSchema')->will($this->returnValue($schemaFile));

        $this->model = $this->objectManager->getObject(
            'Magento\Framework\App\Config\Initial\Reader',
            [
                'fileResolver' => $this->fileResolverMock,
                'converter' => $this->converterMock,
                'schemaLocator' => $this->schemaLocatorMock,
                'validationState' => $this->validationStateMock
            ]
        );
    }
}
