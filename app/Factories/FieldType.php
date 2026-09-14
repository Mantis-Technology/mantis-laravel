<?php

namespace App\Factories;

enum FieldType: string
{
    case TEXT = 'text';
    case NUMBER = 'number';
    case DATE = 'date';
    case SELECT = 'select';
    case CHECKBOX = 'checkbox';
    case RADIO = 'radio';
    case TEXTAREA = 'textarea';
    case EMAIL = 'email';
    case SELECT_MULTIPLE = 'select_multiple';
    case FILE = 'file';
    case URL = 'url';
}
