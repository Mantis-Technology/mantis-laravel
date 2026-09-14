<?php

use App\Factories\FieldFactory;
use App\Factories\FileField;
use App\Factories\NumberField;
use App\Factories\SelectField;
use App\Factories\TextField;

test('creates a text field from its canonical structure', function () {
    $field = FieldFactory::createField([
        'name' => 'title',
        'label' => 'Título',
        'type' => 'text',
        'required' => true,
        'placeholder' => 'Ingrese el título',
        'order' => 1,
    ]);

    expect($field)
        ->toBeInstanceOf(TextField::class)
        ->and($field->toArray())
        ->toBe([
            'name' => 'title',
            'label' => 'Título',
            'type' => 'text',
            'required' => true,
            'order' => 1,
            'placeholder' => 'Ingrese el título',
        ]);
});

test('defaults label to name and omits placeholder when absent', function () {
    $field = FieldFactory::createField([
        'name' => 'code',
        'type' => 'text',
        'required' => false,
        'order' => 2,
    ]);

    expect($field->toArray())->toBe([
        'name' => 'code',
        'label' => 'code',
        'type' => 'text',
        'required' => false,
        'order' => 2,
    ]);
});

test('maps number min and max', function () {
    $field = FieldFactory::createField([
        'name' => 'quantity',
        'label' => 'Cantidad',
        'type' => 'number',
        'required' => false,
        'min' => 0,
        'max' => 100,
        'order' => 3,
    ]);

    expect($field)
        ->toBeInstanceOf(NumberField::class)
        ->and($field->toArray())
        ->toBe([
            'name' => 'quantity',
            'label' => 'Cantidad',
            'type' => 'number',
            'required' => false,
            'order' => 3,
            'min' => 0,
            'max' => 100,
        ]);
});

test('maps select options as value/label pairs', function () {
    $field = FieldFactory::createField([
        'name' => 'category',
        'label' => 'Categoría',
        'type' => 'select',
        'required' => true,
        'options' => [
            ['value' => 'option1', 'label' => 'Option 1'],
            ['value' => 'option2', 'label' => 'Option 2'],
        ],
        'order' => 4,
    ]);

    expect($field)
        ->toBeInstanceOf(SelectField::class)
        ->and($field->toArray())
        ->toBe([
            'name' => 'category',
            'label' => 'Categoría',
            'type' => 'select',
            'required' => true,
            'order' => 4,
            'options' => [
                ['value' => 'option1', 'label' => 'Option 1'],
                ['value' => 'option2', 'label' => 'Option 2'],
            ],
        ]);
});

test('maps file mime types and max size', function () {
    $field = FieldFactory::createField([
        'name' => 'attachment',
        'label' => 'Adjunto',
        'type' => 'file',
        'required' => false,
        'mimeTypes' => ['image/jpeg', 'application/pdf'],
        'maxFileSize' => 5242880,
        'order' => 5,
    ]);

    expect($field)
        ->toBeInstanceOf(FileField::class)
        ->and($field->toArray())
        ->toBe([
            'name' => 'attachment',
            'label' => 'Adjunto',
            'type' => 'file',
            'required' => false,
            'order' => 5,
            'mimeTypes' => ['image/jpeg', 'application/pdf'],
            'maxFileSize' => 5242880,
        ]);
});
