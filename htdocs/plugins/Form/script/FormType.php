<?php

/*-------- InputElement base class {{{------------*/
class InputElement
{
	private $mandatory;
	private $size;
	protected $name;
	private $id;
	private $type;
	private $weight;
	protected $value;
	private $options;
	private $default;
	private $key;
	protected $optionlist;

	public function __construct($type, $name, $mandatory, $size, $options='', $default='')
	{
		$this->setType($type);
		$this->setName($name);
		$this->setMandatory($mandatory);
		$this->setSize($size);
		$this->setOptions($options);
		$this->setValue($default);
		$this->setDefault($default);
		$this->setKey(md5($name));
	}


	public function getType()
	{
		return $this->type;
	}

	public function setType($value)
	{
		$this->type = trim($value);
	}

	public function getKey()
	{
		return $this->key;
	}

	public function setKey($value)
	{
		$this->key = trim($value);
	}

	public function getValue($full=true)
	{
		return $this->value;
	}

	public function __toString()
	{
		return $this->getValue();
	}

	public function setValue($value)
	{
		$this->value =  is_array($value) ? $value : trim($value);
	}

	public function getSize()
	{
		return $this->size;
	}

	public function setSize($value)
	{
		$this->size = trim($value);
	}

	public function getMandatory()
	{
		return $this->mandatory;
	}

	public function setMandatory($value)
	{
		$this->mandatory = trim($value);
	}

	public function getWeight()
	{
		return $this->weight;
	}

	public function setWeight($value)
	{
		$this->weight = $value;
	}

	public function getName()
	{
		return $this->name;
	}

	public function setName($value)
	{
		$this->name = trim($value);
	}

	public function getOptionsList()
	{
		return Utils::nl2array($this->options);
	}

	public function getOptions()
	{
		return $this->options;
	}

	public function setOptions($value)
	{
		$this->options = trim($value);
	}

	public function getDefault()
	{
		return $this->default;
	}

	public function setDefault($value)
	{
		$this->default = trim($value);
	}

	public function getId()
	{
		return $this->id;
	}

	public function setId($value)
	{
		$this->id = $value;
	}


	protected function getKeyValue()
	{
		// change value to md5 string if value is initialized as default value
		if($this->value && !is_array($this->value) && $this->value == $this->getDefault()) $this->setValue(md5($this->getDefault()));
		return $this->value;
	}

	protected function getOptionList()
	{
		if($this->optionlist) return $this->optionlist;
		$this->optionlist = array();

		foreach($this->getOptionsList() as $item)
		{
			$key = md5($item);
			$this->optionlist[$key] = array('id' => $key, 'name' => $item);
		}
		return $this->optionlist;
	}

	public function getOption($key)
	{
		$list = $this->getOptionList();
		if(array_key_exists($key, $list)) return $list[$key];
	}

	public function getOptionKey($name)
	{
		$list = $this->getOptionList();
		$key = md5($name);
		if(array_key_exists($key, $list)) return $key;
	}

	public function handleSetValue($value)
	{
		$this->setValue($value);
	}

	public function handleGetRequest()
	{
		$request = Request::getInstance();
		if($request->exists($this->getId())) $this->setValue($request->getValue($this->getId()));
	}

	public function handlePostRequest()
	{
		$request = Request::getInstance();
		$this->setValue($request->getValue($this->getId()));
	}


	public function getHtml()
	{
		return $this->getName();
	}

	public function validate()
	{
		if($this->getMandatory() && !$this->getValue()) throw new Exception($this->getName()." ontbreekt.");
	}
}
//}}}

/*-------- InputDescription class {{{------------*/
class InputDescription extends InputElement
{
	public function getHtml()
	{
		return;
	}

}
//}}}

/*-------- InputHidden class {{{------------*/
class InputHidden extends InputElement
{
	public function getHtml()
	{
		return sprintf('<input type="hidden" name="%s" value="%s" />', $this->getId(), $this->getValue(false));
	}
}
//}}}

/*-------- InputConstant class {{{------------*/
class InputConstant extends InputElement
{
	public function getHtml()
	{
		return sprintf('%s<input type="hidden" name="%s" value="%s" />', $this->getValue(), $this->getId(), $this->getValue(false));
	}
}
//}}}

/*-------- InputLogin class {{{------------*/
class InputLogin extends InputElement
{
	public function getValue($full=true)
	{
		$authentication = Authentication::getInstance();
		return $authentication->getName();
	}

	public function setValue($value)
	{
		return;
	}

	public function getHtml()
	{
		return;
	}
}
//}}}

/*-------- InputTextField class {{{------------*/
class InputTextField extends InputElement
{
	public function getHtml()
	{
		return sprintf('<input type="text" name="%s" size="%d" value="%s" />', $this->getId(), $this->getSize(), $this->getValue(false));
	}
}
//}}}

/*-------- InputEmail class {{{------------*/
class InputEmail extends InputTextField
{
	public function validate()
	{
		parent::validate();
		if($this->getValue() && !Utils::isEmail($this->getValue())) throw new Exception($this->getName()." is ongeldig email adres.");
	}
}
//}}}

/*-------- InputEmailSender class {{{------------*/
class InputEmailSender extends InputEmail 
{
}
//}}}

/*-------- InputPhone class {{{------------*/
class InputPhone extends InputTextField
{
	public function validate()
	{
		parent::validate();
		if($this->getValue() && !Utils::isPhone($this->getValue())) throw new Exception($this->getName()." is ongeldig telefoonnummer.");
	}
}
//}}}

/*-------- InputNumeric class {{{------------*/
class InputNumeric extends InputTextField
{
	public function validate()
	{
		parent::validate();
		if($this->getValue() && !is_numeric($this->getValue())) throw new Exception($this->getName()." is ongeldige numerieke waarde.");
	}
}
//}}}

/*-------- InputDate class {{{------------*/
class InputDate extends InputTextField
{
	public function validate()
	{
		parent::validate();
		if($this->getValue() && !Utils::isDate(Utils::convertDate($this->getValue()))) throw new Exception($this->getName()." is ongeldige datum.");
	}

	public function getHtml()
	{
		return parent::getHtml()." <em>(dd-mm-yyyy)</em>";
	}
}
//}}}

/*-------- InputTextArea class {{{------------*/
class InputTextArea extends InputElement
{
	public function getHtml()
	{
		return sprintf('<textarea name="%s" rows="5" cols="%d">%s</textarea>', $this->getId(), $this->getSize(), $this->getValue(false));
	}
}
//}}}

/*-------- InputCombo class {{{------------*/
class InputCombo extends InputElement
{

	public function getValue($full=true)
	{
		$optionlist = $this->getOptionList();
		if(array_key_exists($this->value, $optionlist)) return $optionlist[$this->value]['name'];
	}

	public function getHtml()
	{
		$default = $this->getDefault();
		$mandatory = $this->getMandatory();

		// create default optional value
		$optional = $mandatory ? '' : '...' ;

		// check if default value is set and if it is part of the option list, if not part of optionlist, create empty value
		if($default &&!array_key_exists(md5($default), $this->getOptionList())) $optional = $default; 
		
		return sprintf('<select size="1" name="%s">%s</select>', $this->getId(), Utils::getHtmlCombo($this->getOptionList(), $this->getKeyValue(), $optional));
	}
}
//}}}

/*-------- InputRadio class {{{------------*/
class InputRadio extends InputElement
{

	public function getValue($full=true)
	{
		$optionlist = $this->getOptionList();
		if(array_key_exists($this->value, $optionlist)) return $optionlist[$this->value]['name'];
	}

	public function handleSetValue($value)
	{
		foreach(split(',', $value) as $item)
		{
			$item = trim($item);
			$key = $this->getOptionKey($item);
			$this->setValue($key);
			break;
		}
	}

	protected function getSeparator()
	{
		return "<br />";
	}

	public function getHtml()
	{
		$default = $this->getDefault();
		$mandatory = $this->getMandatory();

		return Utils::getHtmlRadio($this->getOptionList(), $this->getKeyValue(), $this->getId(), $this->getSeparator());
	}
}
//}}}

/*-------- InputRadioHorizontal class {{{------------*/
class InputRadioHorizontal extends InputRadio
{
	protected function getSeparator()
	{
		return " ";
	}
}
//}}}

/*-------- InputRadioScale class {{{------------*/
class InputRadioScale extends InputRadio
{
	public function getHtml()
	{
		$retval = '<table class="radioscale"><tr>';
		$radios = array();
		foreach($this->getOptionList() as $item)
		{
			$radios[] = array('id' => $item['id'], 'name' => '');
			$retval .= "<th>{$item['name']}</th>\n";
		}
		$retval .= "</tr><tr>\n<td>";
		$retval .=  Utils::getHtmlRadio($radios, $this->getKeyValue(), $this->getId(), "</td><td>");
		$retval .= "</td>\n</tr></table>";
		return $retval;
	}
}
//}}}

/*-------- InputRadioExtra class {{{------------*/
class InputRadioExtra extends InputRadio
{
	private $extra;

	public function __construct($type, $name, $mandatory, $size, $options='', $default='')
	{
		parent::__construct($type, $name, $mandatory, $size, $options, $default);
		$this->extra = new InputTextField($type, $name, false, $size);
	}

	public function setId($value)
	{
		parent::setId($value);
		$this->extra->setId("e$value");
	}

	public function getValue($full=true)
	{
		$retval = parent::getValue(false);
		if($full && $this->extra->getValue()) $retval .= ", ".$this->extra->getValue();
		return $retval;
	}

	public function handleSetValue($value)
	{
		$options = $this->getOptionsList();
		foreach($options as $item)
		{
			$pos = strpos($value, $item);
			if($pos === false) continue;
			$this->setValue($this->getOptionKey($item));
			$value = substr($value, strlen($item)+1);
			break;
		}
		$this->extra->setValue(trim($value));
	}

	public function handleGetRequest()
	{
		$request = Request::getInstance();
		if($request->exists($this->getId())) $this->setValue($request->getValue($this->getId()));
		if($request->exists($this->extra->getId())) $this->extra->setValue($request->getValue($this->extra->getId()));
	}

	public function handlePostRequest()
	{
		$request = Request::getInstance();
		$this->setValue($request->getValue($this->getId()));
		$this->extra->setValue($request->getValue($this->extra->getId()));
	}

	public function getHtml()
	{
		return parent::getHtml()." ".$this->extra->getHtml();
	}
}
//}}}

/*-------- InputCheckbox class {{{------------*/
class InputCheckbox extends InputElement
{
	public function getHtml()
	{
		$selected = $this->getValue() ? 'checked' : '';
		return sprintf('<input class="noborder" type="checkbox" name="%s" value="%s" %s />', $this->getId(), $this->getName(), $selected);
	}
}
//}}}

/*-------- InputMultiCheckbox class {{{------------*/
class InputMultiCheckbox extends InputElement
{

	public function handleSetValue($value)
	{
		$retval = array();
		foreach(split(',', $value) as $item)
		{
			$item = trim($item);
			$key = $this->getOptionKey($item);
			if($key) $retval[] = $key;
		}
		$this->setValue($retval);
	}

	public function getValue($full=true)
	{
		$retval = array();
		$optionlist = $this->getOptionList();
		$values = is_array($this->value) ? $this->value : array($this->value);
		foreach($values as $item)
		{
			if(array_key_exists($item, $optionlist)) $retval[] = $optionlist[$item]['name'];
		}
		return $retval;
	}

	public function __toString()
	{
		return join(', ', $this->getValue());
	}

	protected function getSeparator()
	{
		return "<br />";
	}

	public function getHtml()
	{
		$default = $this->getDefault();
		$mandatory = $this->getMandatory();

		return Utils::getHtmlCheckbox($this->getOptionList(), $this->getKeyValue(), $this->getId(), $this->getSeparator());
	}
}
//}}}

/*-------- InputMultiCheckboxHorizontal class {{{------------*/
class InputMultiCheckboxHorizontal extends InputMultiCheckbox
{
	protected function getSeparator()
	{
		return " ";
	}
}
//}}}

/*-------- InputMultiCheckboxExtra class {{{------------*/
class InputMultiCheckboxExtra extends InputMultiCheckbox
{
	private $extra;

	public function __construct($type, $name, $mandatory, $size, $options='', $default='')
	{
		parent::__construct($type, $name, $mandatory, $size, $options, $default);
		$this->extra = new InputTextField($type, $name, false, $size);
	}

	public function setId($value)
	{
		parent::setId($value);
		$this->extra->setId("e$value");
	}

	public function getValue($full=true)
	{
		$retval = parent::getValue(false);
		if($full && $this->extra->getValue()) $retval[] = " ".$this->extra->getValue();
		return $retval;
	}

	public function handleSetValue($value)
	{
		$pos = 0;
		$retval = array();
		$values = split(',', $value);
		$lastval = array_pop($values);

		foreach($values as $item)
		{
			$item = trim($item);
			$key = $this->getOptionKey($item);
			if($key) 
			{
				$retval[] = $key;
			}
		}

		// get optional value
		$options = $this->getOptionsList();
		foreach($options as $item)
		{
			$pos = strpos($lastval, $item);
			if($pos === false) continue;
			$retval[] = $this->getOptionKey($item);
			$lastval = substr($lastval, strlen($item)+1);
			break;
		}

		$this->extra->setValue(trim($lastval));
		$this->setValue($retval);
	}

	public function handleGetRequest()
	{
		$request = Request::getInstance();
		if($request->exists($this->getId())) $this->setValue($request->getValue($this->getId()));
		if($request->exists($this->extra->getId())) $this->extra->setValue($request->getValue($this->extra->getId()));
	}

	public function handlePostRequest()
	{
		$request = Request::getInstance();
		$this->setValue($request->getValue($this->getId()));
		$this->extra->setValue($request->getValue($this->extra->getId()));
	}

	public function getHtml()
	{
		return parent::getHtml()." ".$this->extra->getHtml();
	}
}
//}}}
?>
