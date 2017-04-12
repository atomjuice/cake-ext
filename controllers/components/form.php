<?php

class FormComponent extends Component
{
    protected $fields = [];

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public function beforeRedirect(&$controller, $url, $status = null, $exit = true)
    {
        parent::beforeRedirect($controller, $url, $status, $exit);
    }

    public function __construct(array $fields = [])
    {
        $this->addFields($fields);
        parent::__construct();
    }

    public function getFields()
    {
        return $this->fields;
    }

    public function getField($name)
    {
        $this->hasField($name, true);
        return $this->fields[$name];
    }

    public function addField($name, $value = '', $error = null)
    {
        $this->fields[$name] = ['value' => $value, 'error' => $error];
    }

    public function addFields(array $fields)
    {
        foreach ($fields as $field) {
            $this->addField($field);
        }
    }

    public function hasField($field, $exception = false)
    {
        if (isset($this->fields[$field])) {
            return true;
        }
        if ($exception) {
            throw new Exception('Form element does not exist');
        }
        return false;
    }

    public function removeField($name)
    {
        $this->hasField($name, true);
        unset($this->fields[$name]);
    }

    public function setFieldValues($fields)
    {
        foreach ($fields as $name => $value) {
            if ($this->hasField($name)) {
                $this->fields[$name]['value'] = $value;
            }
        }
    }

    public function getValue($field)
    {
        $this->hasField($field, true);
        return $this->fields[$field]['value'];
    }

    public function getValues()
    {
        $values = [];
        foreach ($this->fields as $name => $field) {
            $values[$name] = $field['value'];
        }
        return $values;
    }

    public function setError($field, $errorMessage)
    {
        $this->hasField($field, true);
        $this->fields[$field]['error'] = $errorMessage;
    }

    public function setErrors($fields)
    {
        foreach ($fields as $name => $value) {
            $this->setError($name, $value);
        }
    }

    public function getError($field)
    {
        $this->hasField($field, true);
        return $this->fields[$field]['error'];
    }

    public function hasError($field)
    {
        $this->hasField($field, true);
        return isset($this->fields[$field]['error']);
    }
}
