<?php defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Input\Input;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;

JLoader::register('AttrsHelper', JPATH_ADMINISTRATOR . '/components/com_attrs/helpers/attrs.php');


/**
 * Class plgSystemExtendattrs
 */
class plgSystemExtendattrs extends CMSPlugin
{

    /**
     * @var Input
     */
    private $input;


    /**
     * @var boolean
     */
    private $isAdmin;


    /**
     * plgSystemExtendattrs constructor.
     * @param $subject
     * @param $config
     */
    public function __construct(&$subject, $config)
    {
        parent::__construct($subject, $config);

        $this->input = new Input();
        $this->isAdmin = Factory::getApplication()->isClient('administrator');
        $this->loadLanguage();
    }


    /**
     * @param $form
     * @param $data
     * @return bool|void
     */
    public function onContentPrepareForm($form, $data)
    {
        if (!$this->isAdmin)
        {
            return;
        }

        if (!($form instanceof Form))
        {
            return;
        }

        $context = $this->input->getCmd('option', '') . '.' . $this->input->getCmd('view', '');
        $formname = $form->getName();
        $contexts = $this->params->get('context', []);
        $find = false;

        foreach ($contexts as $context_param)
        {
            if($context_param->name === $context)
            {
                $find = $context_param;
            }
        }

        if(!$find)
        {
            return;
        }


        $fields = $this->getData((array)$context_param->field_ids);


        if (!$fields)
        {
            return;
        }

        if (is_array($data))
        {
            $data = (object) $data;
        }

        $xml = '<?xml version="1.0" encoding="utf-8"?><form>';
        $xml .= '<fieldset name="' . $context_param->fieldset_name . '" label="' . Text::_('PLG_SYSTEM_EXTENDATTRS_TAB_LABEL') . '">';
        $xml .= '<fields name="' . $context_param->fields_name . '">';

        foreach ($fields as $field)
        {

            $name = ' name="attrs_' . $field->name . '"';
            $label = ' label="' . $field->title . '"';
            $class = $field->class ? ' class="' . $field->class . '"' : ' class="input-xlarge"';

            switch ($field->tp)
            {
                case 'text':
                    $xml .= '<field type="text"' . $name . $label . $class . ($field->filter ? ' filter="' . $field->filter . '"' : '') . '/>';
                    break;

                case 'textarea':
                    $xml .= '<field type="textarea"' . $name . $label . $class . ($field->filter ? ' filter="' . $field->filter . '"' : '') . ' rows="5"/>';
                    break;

                case 'editor':
                    $xml .= '<field type="editor"' . $name . $label . ' filter="raw"/>';
                    break;

                case 'list':
                    $xml .= '<field type="list"' . $name . $label . $class . ($field->multiple ? ' multiple="true"' : '') . '>';
                    foreach ($field->val as $val)
                    {
                        $xml .= '<option value="' . $val['vname'] . '">' . $val['vtitle'] . '</option>';
                    }
                    $xml .= '</field>';
                    break;

                case 'media':
                    $xml .= '<field type="media"' . $name . $label . $class . '/>';
                    break;
            }
        }

        $xml .= '</fields>';
        $xml .= '</fieldset>';
        $xml .= '</form>';

        $xml = new \SimpleXMLElement($xml);
        $form->setFields($xml, null, false);
        return true;
    }


    /**
     * @param array $ids
     * @return array
     */
    private function getData($ids = [])
    {
        $db = Factory::getDbo();

        $query = $db->getQuery(true)
            ->select('`name`, `title`, `tp`, `val`, `multiple`, `filter`, `class`')
            ->from('`#__attrs`')
            ->where($db->quoteName('id') . ' IN (' . implode(',', $ids). ')' )
            ->where('`published` = 1')
            ->order('`id` asc');

        try
        {
            $list = $db->setQuery($query)->loadObjectList();
        }
        catch (Exception $e)
        {
            $list = [];
        }

        if ($list)
        {
            foreach ($list as &$item)
            {
                if ($item->tp === 'list' && $item->val !== '')
                {
                    $item->val = json_decode($item->val, true);
                }
            }
        }

        return $list;
    }


}
