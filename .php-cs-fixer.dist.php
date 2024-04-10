<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$finder = Finder::create()
    ->in([__DIR__])
    ->exclude(['vendor'])
    ->ignoreDotFiles(false);

return (new Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@PhpCsFixer' => true,
        '@PhpCsFixer:risky' => true,
        '@PHP74Migration' => true,
        '@PHP74Migration:risky' => true,

        // required by PSR-12
        'concat_space' => [
            'spacing' => 'one',
        ],

        // disable some too strict rules
        'phpdoc_types_order' => [
            'null_adjustment' => 'always_last',
            'sort_algorithm' => 'none',
        ],
        'single_line_throw' => false,
        'yoda_style' => [
            'equal' => false,
            'identical' => false,
        ],
        'native_constant_invocation' => true,
        'native_function_invocation' => false,
        'void_return' => false,
        'blank_line_before_statement' => [
            'statements' => ['break', 'continue', 'declare', 'return', 'throw', 'exit'],
        ],
        'final_internal_class' => false,
        'combine_consecutive_issets' => false,
        'combine_consecutive_unsets' => false,
        'multiline_whitespace_before_semicolons' => false,
        'no_superfluous_elseif' => false,
        'ordered_class_elements' => false,
        'php_unit_internal_class' => false,
        'php_unit_test_class_requires_covers' => false,
        'phpdoc_add_missing_param_annotation' => false,
        'return_assignment' => false,
        'comment_to_phpdoc' => false,
        'general_phpdoc_annotation_remove' => [
            'annotations' => ['author', 'copyright', 'throws'],
        ],

        // fn => without curly brackets is less readable,
        // also prevent bounding of unwanted variables for GC
        'use_arrow_functions' => false,

        // TODO
        'align_multiline_comment' => false,
        'array_indentation' => false,
        'binary_operator_spaces' => false,
        'blank_line_after_opening_tag' => false,
        'blank_line_before_statement' => false,
        'concat_space' => false,
        'control_structure_continuation_position' => false,
        'declare_strict_types' => false,
        'explicit_string_variable' => false,
        'function_declaration' => false,
        'function_to_constant' => false,
        'general_phpdoc_annotation_remove' => false,
        'include' => false,
        'list_syntax' => false,
        'method_argument_space' => false,
        'native_constant_invocation' => false,
        'new_with_parentheses' => false,
        'no_alias_functions' => false,
        'no_empty_phpdoc' => false,
        'no_spaces_after_function_name' => false,
        'no_superfluous_phpdoc_tags' => false,
        'ordered_imports' => false,
        'phpdoc_indent' => false,
        'phpdoc_no_alias_tag' => false,
        'phpdoc_no_package' => false,
        'phpdoc_separation' => false,
        'phpdoc_summary' => false,
        'single_line_empty_body' => false,
        'single_quote' => false,
        'single_space_around_construct' => false,
        'spaces_inside_parentheses' => false,
        'static_lambda' => false,
        'strict_comparison' => false,
        'strict_param' => false,
        'string_implicit_backslashes' => false,
        'yoda_style' => false,
    ])
    ->setFinder($finder)
    ->setCacheFile(sys_get_temp_dir() . '/php-cs-fixer.' . md5(__DIR__) . '.cache');