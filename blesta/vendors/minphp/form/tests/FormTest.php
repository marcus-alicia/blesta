<?php
namespace Minphp\Form\Tests;

use PHPUnit_Framework_TestCase;
use Minphp\Form\Form;

/**
 * @coversDefaultClass \Minphp\Form\Form
 */
class FormTest extends PHPUnit_Framework_TestCase
{
    public function testConstruct()
    {
        $this->assertInstanceOf('\Minphp\Form\Form', new Form());
    }

    /**
     * @covers ::fieldSelect
     * @covers ::buildAttributes
     * @covers ::selectOptions
     * @covers ::setOutput
     * @covers ::output
     *
     * @dataProvider selectProvider
     */
    public function testFieldSelect(
        $expected,
        $name,
        array $options = array(),
        $selected_value = null,
        array $attributes = array(),
        array $option_attributes = array()
    ) {
        $form = new Form();
        $form->setOutput(true);

        $html = $form->fieldSelect($name, $options, $selected_value, $attributes, $option_attributes);

        $this->assertEquals($expected, $html);
    }

    /**
     * @covers ::fieldMultiSelect
     * @covers ::fieldSelect
     * @covers ::buildAttributes
     * @covers ::selectOptions
     * @covers ::setOutput
     * @covers ::output
     *
     * @dataProvider selectProvider
     */
    public function testFieldMultiSelect(
        $expected,
        $name,
        array $options = array(),
        $selected_value = null,
        array $attributes = array(),
        array $option_attributes = array()
    ) {
        $form = new Form();
        $form->setOutput(true);

        $html = $form->fieldMultiSelect($name, $options, $selected_value, $attributes, $option_attributes);

        // Expect the same output as a normal select, except the <select> field should be a <select multiple="multiple">
        $expected = preg_replace('/\<select(.*)\>/', '<select${1} multiple="multiple">', $expected);
        $this->assertEquals($expected, $html);
    }

    /**
     * Data Provider for ::testFieldSelect, ::testFieldMultiSelect
     */
    public function selectProvider()
    {
        $test_option =<<<TESTOPTION
<select name="espa&ntilde;ol">
<option value="espa&ntilde;ol">espa&ntilde;ol</option>
</select>

TESTOPTION;

        $test_optgroup =<<<TESTOPTGROUP
<select name="test" data-test="abc123" attr="&quot;&gt;">
<option value="no-group">No Group</option>
<optgroup label="espa&ntilde;ol">
<option value="espa&ntilde;ol1">Select Espa&ntilde;ol</option>
</optgroup>
<optgroup label="language">
<option value="espa&ntilde;ol2" selected="selected">Select Espa&ntilde;ol</option>
<option class="my classes" id="lang-en" value="ENGLISH">Select English</option>
</optgroup>
<optgroup label="new optgroup">
<option value="test 111 &ntilde;&gt;&iacute;">Test 111 &ntilde;&gt;&iacute;</option>
</optgroup>
</select>

TESTOPTGROUP;

        return array(
            array("<select name=\"espa&ntilde;ol\">\n</select>\n", 'español', array(), null, array(), array()),
            array($test_option, 'español', array('español' => 'español'), null, array(), array()),
            array(
                $test_optgroup,
                'test',
                array(
                    array('name' => 'No Group', 'value' => 'no-group'),
                    array('name' => 'español', 'value' => 'optgroup'),
                    array('name' => 'Select Español', 'value' => 'español1'),
                    array('name' => '', 'value' => 'close_optgroup'),
                    array('name' => 'language', 'value' => 'optgroup'),
                    array('name' => 'Select Español', 'value' => 'español2'),
                    array('name' => 'Select English', 'value' => 'ENGLISH'),
                    // Set a new optgroup without ending the previous one
                    array('name' => 'new optgroup', 'value' => 'optgroup'),
                    array('name' => 'Test 111 ñ>í', 'value' => 'test 111 ñ>í'),
                ),
                'español2',
                array('data-test' => 'abc123', 'attr' => '">'),
                array('ENGLISH' => array('class' => 'my classes', 'id' => 'lang-en'))
            ),
        );
    }
}
