<?php

declare(strict_types=1);

namespace Symbiotic\Settings;

interface SettingsFormInterface
{
    /**
     * @key type in field
     */
    const TEXT = 'text';
    const TEXTAREA = 'textarea';
    const SELECT = 'select';
    const RADIO = 'radio';
    const CHECKBOX = 'checkbox';
    const BOOL = 'bool';
    const PASSWORD = 'password';

    /**
     * The group of fields in which the collection can be located
     */
    const GROUP = 'group';


    /**
     * @return array
     * @todo It will be necessary to make fields on the classes and give them a collection
     */
    public function getFields(): array;
}