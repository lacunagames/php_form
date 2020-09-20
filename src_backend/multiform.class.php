<?php
class MultiForm
{
  public $formTitle = 'Multi Form';
  public $legendTitle = 'Default legend';
  public $legendName = 'legend';
  public $fieldTypes = [];
  public $values = [];
  public $fieldErrors = [];
  public $formMessage;


  public function __construct($config, $values = [], $actionWithIndex = '')
  {
    [$action, $index] = explode('-', $actionWithIndex . '-');
    $this->formTitle = $config->formTitle;
    $this->legendTitle = $config->legendTitle;
    $this->legendName = $config->legendName;
    $this->fieldTypes = $config->fieldTypes;
    foreach ($this->fieldTypes as $field) {
      $field->name = str_replace(' ', '-', strtolower($field->title));
    }
    if (isset($values)) {
      $this->values = array_values($values);
    }
    if (!isset($values) || ($action == 'add' && $this->validateValues())) {
      $this->addSet();
    }
    if ($action == 'remove') {
      $this->removeSet($index);
    }
    if ($action == 'save' && $this->validateValues()) {
      $this->saveForm();
    }
  }


  public function removeSet($index)
  {
    array_splice($this->values, $index, 1);
  }


  public function addSet()
  {
    $index = count($this->values);
    $this->values[$index] = (object)[];
    foreach ($this->fieldTypes as $field) {
      $this->values[$index]->{$field->name} = '';
    }
  }


  public function validateValues()
  {
    $this->fieldErrors = [];
    $isValid = true;

    foreach ($this->values as $idx => $set) {
      foreach ($set as $fieldName => $value) {
        $fieldType = $this->getFieldType($fieldName);
        $invalidType = $this->getInvalidType($value, $fieldType->rules);
        if ($invalidType) {
          $isValid = false;
          $this->fieldErrors["field-$idx-$fieldName"] = $invalidType;
        }
      }
    }
    if (!$isValid) {
      $this->formMessage = (object)[
        'text' => 'Invalid form input value. Please review and try again.',
        'type' => 'error'
      ];
    }

    return $isValid;
  }


  public function saveForm()
  {
    $date = date("H:i:s — F j, Y");
    $content = "\n—— Following record saved at $date ——\n\n";
    foreach ($this->values as $idx => $set) {
      $content .= "$this->legendTitle " . ($idx + 1) . "\n";
      foreach ($set as $fieldName => $value) {
        $fieldType = $this->getFieldType($fieldName);
        $content .= $fieldType->title . ": " . $value . "\n";
      }
      $content .= "\n";
    }

    $filename = "../saved_data/$this->legendName.txt";
    $ret = file_put_contents($filename, $content, FILE_APPEND | LOCK_EX);
    if ($ret === false) {
      $this->formMessage = (object)[
        'text' => 'There was a problem saving your data.',
        'type' => 'error'
      ];
    } else {
      $this->values = [];
      $this->addSet();
      $this->formMessage = (object)[
        'text' => 'Your data has been successfully saved.',
        'type' => 'success'
      ];
    }
  }


  private function getInvalidType($value, $rules)
  {
    foreach ($rules as $rule) {
      switch ($rule) {
        case 'required':
          if (strlen($value) === 0) {
            return $rule;
          }
          break;

        case 'name':
          if (!ctype_alpha(str_replace(' ', '', $value))) {
            return $rule;
          }
          break;

        case 'email':
          if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return $rule;
          }
          break;

        case 'phone':
          if (!ctype_digit($value)) {
            return $rule;
          }
          break;
      }
    }
    return null;
  }


  private function getFieldType($fieldName)
  {
    foreach ($this->fieldTypes as $fieldType) {
      if ($fieldType->name === $fieldName) {
        return $fieldType;
      }
    }
  }

  private function getErrorMessage($rule)
  {
    switch ($rule) {
      case 'required':
        return 'This field is required.';

      case 'name':
        return 'Invalid name. Only letters and space are allowed.';

      case 'email':
        return 'Invalid email address. E.g. abc34@website.com';

      case 'phone':
        return 'Invalid phone number. Only numbers are allowed.';

      default:
        return '';
    }
  }


  public function printJson()
  {
    $data = (object)[
      'message' => $this->formMessage,
      'fieldErrors' => $this->fieldErrors,
    ];
    return json_encode($data);
  }


  public function printForm()
  {
    $formMessage = '';
    if (isset($this->formMessage->text)) {
      $formMessage = <<<MESSAGE
      <div class="message {$this->formMessage->type}">
        <p>{$this->formMessage->text}</p>
      </div>  
MESSAGE;
    }
    $content = <<<FORM_START
<form class="multiform" id="$this->legendName-form" method="post">
  <div class="heading">
    <h2 class="h1">$this->formTitle</h2>
  </div>
  $formMessage
  <div class="actions">
    <button type="submit" name="action" value="save" class="primary">Save<span class="access"> Form</span></button>
    <button type="submit" name="action" value="add">Add <span class="access">New </span>$this->legendTitle</button>
  </div>\n
FORM_START;

    $content .= "<div class='fieldsets'>\n";
    foreach ($this->values as $idx => $set) {
      $index = $idx + 1;
      $content .= <<<SET_START
    <fieldset>
      <legend>$this->legendTitle<span class="access"> $index</span></legend>\n
SET_START;

      foreach ($set as $fieldName => $value) {
        $fieldType = $this->getFieldType($fieldName);
        $rules = implode(" | ", $fieldType->rules);
        $errorMessage = isset($this->fieldErrors["field-$idx-$fieldName"])
          ? $this->getErrorMessage($this->fieldErrors["field-$idx-$fieldName"]) : '';
        $content .= <<<FIELD
      <div class="input-row">  
        <label for="$this->legendName[$idx][$fieldName]">$fieldType->title</label>
        <input 
          type="text" 
          name="$this->legendName[$idx][$fieldName]" 
          id="$this->legendName[$idx][$fieldName]"
          value="$value"
          data-rules="$rules" />
        <p class="error">$errorMessage</p>
      </div>\n
FIELD;
      }
      $content .=  <<<SET_END
        <button type="submit" name="action" value="remove-$idx">
          Remove $this->legendTitle<span class="access"> $index</span>
        </button>
      </fieldset>\n
SET_END;
    }
    $content .= <<<FORM_END
    </div>\n
  </form>\n
FORM_END;
    return $content;
  }
}
