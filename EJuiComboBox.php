<?php

/**
 * jQuery combobox Yii extension
 * 
 * Allows selecting a value from a dropdown list or entering in text.
 * Also works as an autocomplete for items in the select.
 *
 * @copyright © Digitick <www.digitick.net> 2011
 * @license GNU Lesser General Public License v3.0
 * @author Ianaré Sévi
 * @author Jacques Basseck
 *
 */
Yii::import('zii.widgets.jui.CJuiInputWidget');

/**
 * Base class.
 */
class EJuiComboBox extends CJuiInputWidget
{
	/**
	 * @var array the entries that the autocomplete should choose from.
	 */
	public $data = array();
	/**
	 * @var string A jQuery selector used to apply the widget to the element(s).
	 * Use this to have the elements keep their binding when the DOM is manipulated
	 * by Javascript, ie ajax calls or cloning.
	 * Can also be useful when there are several elements that share the same settings,
	 * to cut down on the amount of JS injected into the HTML.
	 */
	public $scriptSelector;
	public $defaultOptions = array('allowText' => true);
	public $ajaxRefresh = false; 
	// ^--- Addition by c@cba - To make widget work as filter in CGridView after refresh. See also related addition below.

	protected function setSelector($id, $script, $event=null)
	{
		if ($this->scriptSelector) {
			if (!$event)
				$event = 'focusin';
			$js = "jQuery('body').delegate('{$this->scriptSelector}','{$event}',function(e){\$(this).{$script}});";
			$id = $this->scriptSelector;
		}
		else
			$js = "jQuery('#{$id}').{$script}";
		return array($id, $js);
	}

	public function init()
	{
		/*$assets = Yii::app()->getAssetManager()->publish(dirname(__FILE__) . '/assets');
		$cs->registerScriptFile($assets . '/jquery.ui.widget.min.js');
		$cs->registerScriptFile($assets . '/jquery.ui.combobox.js');*/
		/*
		 * Addition by c@cba - start
		 * To resolve button conflict between bootstrap and jQueryUI ( see https://github.com/twbs/bootstrap/issues/6094 )
		 * In the main config file "protected/config/main.php", place the code:
			'components'=>array(
			...
				'clientScript'=>array(
					'packages'=>array(
						'bootstrap'=>array(
							'basePath'=>'webroot.my_assets',
							'js'=>array('js/bootstrap.js'),
							'css'=>array('css/bootstrap.css'),
							'depends'=>array('jquery', 'jquery.ui'),
						),
						'bs_button_noconflict'=>array(
							'basePath'=>'webroot.my_assets',
							'js'=>array('js/bs_button_noconflict.js'),
							'depends'=>array('bootstrap'),
						),
					),
				),
			),
		 * In the main layout file "protected/views/layouts/main.php", place the code:
			$cs = Yii::app()->getClientScript();
			$cs->registerCoreScript('jquery');
			$cs->registerCoreScript('jquery.ui');
			$cs->registerPackage('bootstrap');
			$cs->registerPackage('bs_button_noconflict');
		 */
		$cs = Yii::app()->getClientScript();
		$cs->addPackage('combobox',array(
			'basePath'=>'ext.combobox.assets',
			'js'=>array('jquery.ui.widget.min.js', 'jquery.ui.combobox.js'),
			'depends'=>array('jquery', 'jquery.ui', 'bootstrap', 'bs_button_noconflict'),
		));
		$cs->registerPackage('combobox');
		$this->defaultOptions['showAllText'] = Yii::t('EJuiComboBox.EJuiComboBox','Show All Items');
		
		/* Addition by c@cba - end */
		parent::init();
	}

	/**
	 * Run this widget.
	 * This method registers necessary javascript and renders the needed HTML code.
	 */
	public function run()
	{
		list($name, $id) = $this->resolveNameID();

		if (is_array($this->data) && !empty($this->data)){
			$data = array_combine($this->data, $this->data);
			array_unshift($data, null);
		}
		else
			$data = array();

		echo CHtml::dropDownList(null, null, $data, array('id' => $id . '_select'));
		/*
		 * Modification by c@cba - start
		 * Hiding the select-element by adding the css-style 'display:none' to the dropDownHtmlOptions
		 * is not preferable in terms of graceful degradation (for JS-disabled) >> removed that style element.
		 * Wanted to hide the select-element directly after the element is rendered, by using the following code:
		 * echo '<script type="text/javascript"> $("#'.$id.'_select").hide(); </script>';
		 * ( see: http://www.electrictoolbox.com/jquery-hide-text-page-load-show-later/ )
		 * Problem: (16.01.2013) when thus hidden, in the js-script the select-element cannot be found, 
		 * 	its options cannot be matched. autocomplete does not work...
		 * Modification by c@cba - end
		 */

		if ($this->hasModel())
			echo CHtml::activeTextField($this->model, $this->attribute, $this->htmlOptions);
		else
			echo CHtml::textField($name, $this->value, $this->htmlOptions);

		$this->options = array_merge($this->defaultOptions, $this->options);

		$options = CJavaScript::encode($this->options);

		$cs = Yii::app()->getClientScript();

		$js = "combobox({$options});";
		
		list($id, $js) = $this->setSelector($id, $js);
		
		
		/* 
		 * Addition by c@cba - start 
		 * Re-initiate combobox after the part of the page containing it is refreshed via ajax.
		 */
		if($this->ajaxRefresh) { // Idea by jeremy@Yii, see comment on EchMultiSelect extension page...
			$js .= "jQuery('body').ajaxComplete(function() { $js });";
		}
		/* Addition by c@cba - end */

		$cs->registerScript(__CLASS__ . '#' . $id, $js);
	}

}
