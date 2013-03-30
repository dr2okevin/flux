<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011 Claus Due <claus@wildside.dk>, Wildside A/S
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 *****************************************************************/

/**
 * Base class for all FlexForm fields.
 *
 * @package Flux
 * @subpackage ViewHelpers/Flexform/Field
 */
abstract class Tx_Flux_ViewHelpers_Flexform_Field_AbstractFieldViewHelper extends Tx_Flux_Core_ViewHelper_AbstractFlexformViewHelper {

	/**
	 * Initialize arguments
	 * @return void
	 */
	public function initializeArguments() {
		$this->registerArgument('name', 'string', 'Name of the attribute, FlexForm XML-valid tag name string', TRUE);
		$this->registerArgument('label', 'string', 'Label for the attribute, can be LLL: value. Optional - if not specified, Flux ' .
			'tries to detect an LLL label named "flux.fields.fluxFormId.foobar" based on field name, in scope of extension ' .
			'rendering the Flux form. If field is in an object, use "flux.objects.fluxFormId.objectname.foobar" where ' .
			'"foobar" is the name of the field.', FALSE, NULL);
		$this->registerArgument('default', 'string', 'Default value for this attribute');
		$this->registerArgument('required', 'boolean', 'If TRUE, this attribute must be filled when editing the FCE', FALSE, FALSE);
		$this->registerArgument('repeat', 'integer', 'Number of times to repeat field while appending number to name', FALSE, 1);
		$this->registerArgument('exclude', 'boolean', 'If TRUE, this field becomes an "exclude field" (see TYPO3 documentation about this)', FALSE, FALSE);
		$this->registerArgument('transform', 'string', 'Set this to transform your value to this type - integer, array (for csv values), float, DateTime, Tx_MyExt_Domain_Model_Object or ObjectStorage with type hint. Also supported are FED Resource classes.');
		$this->registerArgument('enabled', 'boolean', 'If FALSE, disables the field in the FlexForm', FALSE, TRUE);
		$this->registerArgument('requestUpdate', 'boolean', 'If TRUE, the form is force-saved and reloaded when field value changes', FALSE, NULL);
		$this->registerArgument('displayCond', 'string', 'Optional "Display Condition" (TCA style) for this particular field', FALSE, NULL);
	}

	/**
	 * Get a base configuration containing all shared arguments and their values
	 *
	 * @return array
	 */
	protected function getBaseConfig() {
		if ($this->viewHelperVariableContainer->exists('Tx_Flux_ViewHelpers_FlexformViewHelper', 'sheet')) {
			$sheet = $this->viewHelperVariableContainer->get('Tx_Flux_ViewHelpers_FlexformViewHelper', 'sheet');
		} else {
			$sheet = array(
				'name' => 'options',
				'label' => 'Options',
			);
		}
		$wizardXML = NULL;
		if ($this->viewHelperVariableContainer->exists('Tx_Flux_ViewHelpers_FlexformViewHelper', 'wizards')) {
			$wizardsBackup = $this->viewHelperVariableContainer->get('Tx_Flux_ViewHelpers_FlexformViewHelper', 'wizards');
			$this->viewHelperVariableContainer->remove('Tx_Flux_ViewHelpers_FlexformViewHelper', 'wizards');
		}
		if ($this->viewHelperVariableContainer->exists('Tx_Flux_ViewHelpers_FlexformViewHelper', 'section')) {
			$section = $this->viewHelperVariableContainer->get('Tx_Flux_ViewHelpers_FlexformViewHelper', 'section');
			$sectionName = $section['name'];
		} else {
			$sectionName = NULL;
		}
		$this->viewHelperVariableContainer->addOrUpdate('Tx_Flux_ViewHelpers_FlexformViewHelper', 'fieldName', $this->arguments['name']);
		$this->renderChildren();
		$this->viewHelperVariableContainer->remove('Tx_Flux_ViewHelpers_FlexformViewHelper', 'fieldName');
		if ($sectionName !== NULL) {
			if ($this->viewHelperVariableContainer->exists('Tx_Flux_ViewHelpers_FlexformViewHelper', 'sectionObjectName')) {
				$sectionObjectName = $this->viewHelperVariableContainer->get('Tx_Flux_ViewHelpers_FlexformViewHelper', 'sectionObjectName');
			} else {
				$sectionObjectName = $sectionName . 'Wrap';
			}
		} else {
			$sectionObjectName = NULL;
		}
		if ($this->viewHelperVariableContainer->exists('Tx_Flux_ViewHelpers_FlexformViewHelper', 'wizards')) {
			$wizards = $this->viewHelperVariableContainer->get('Tx_Flux_ViewHelpers_FlexformViewHelper', 'wizards');
			$this->viewHelperVariableContainer->remove('Tx_Flux_ViewHelpers_FlexformViewHelper', 'wizards');
			if ($wizardsBackup) {
				$this->viewHelperVariableContainer->addOrUpdate('Tx_Flux_ViewHelpers_FlexformViewHelper', 'wizards', $wizardsBackup);
			}
			$wizardXML = '';
			foreach ($wizards as $xmlOrArray) {
				if (is_array($xmlOrArray)) {
					$wizardXML .= t3lib_div::array2xml($xmlOrArray, '', 1, key($xmlOrArray));
				} else {
					$wizardXML .= $xmlOrArray;
				}
			}
		}
		if (FALSE === strpos($this->arguments['name'], '.')) {
			$segmentsOfAssignedVariableName = array($this->arguments['name']);
		} else {
			$segmentsOfAssignedVariableName = explode('.', $this->arguments['name']);
		}
		$firstSegmentOfAssignedVariableName = array_shift($segmentsOfAssignedVariableName);
		if (TRUE === $this->templateVariableContainer->exists($firstSegmentOfAssignedVariableName)) {
			$value = $this->templateVariableContainer->get($firstSegmentOfAssignedVariableName);
			if (0 !== count($segmentsOfAssignedVariableName)) {
				$value = Tx_Extbase_Reflection_ObjectAccess::getPropertyPath($value, implode('.', $segmentsOfAssignedVariableName));
			}
			$defaultValue = $value;
		} elseif (TRUE === isset($this->arguments['default'])) {
			$defaultValue = $this->arguments['default'];
		}
		return array(
			'name' => $this->arguments['name'],
			'transform' => $this->arguments['transform'],
			'label' => $this->getLabel(),
			'type' => $this->arguments['type'],
			'default' => $defaultValue,
			'required' => $this->getFlexFormBoolean($this->arguments['required']),
			'repeat' => $this->arguments['repeat'],
			'enabled' => $this->arguments['enabled'],
			'requestUpdate' => $this->arguments['requestUpdate'],
			'displayCond' => $this->arguments['displayCond'],
			'exclude' => $this->getFlexFormBoolean($this->arguments['exclude']),
			'wizards' => $wizardXML,
			'sheet' => $sheet,
			'wrap' => TRUE,
			'section' => $sectionName,
			'sectionObjectName' => $sectionObjectName,
		);
	}

	/**
	 * Get 1 or 0 from a boolean
	 *
	 * @param integer $value
	 * @return integer
	 */
	protected function getFlexFormBoolean($value) {
		return ($value === TRUE ? 1 : 0);
	}

}
