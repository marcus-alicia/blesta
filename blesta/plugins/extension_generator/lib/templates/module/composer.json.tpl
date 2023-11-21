{
    "_comment": "This is currently non-functional.  Use this file to make your module installable through composer.  Replace 'parent_repository' in the name property below with the appropriate location",
    "name": "parent_repository/{{snake_case_name}}",
    "description": "{{description}}",
    "license": "proprietary",
    "type": "blesta-module",
    "require": {
        "blesta/composer-installer": "~1.0"
    },
    "authors": [{{array:authors}}
        {
            "name": "{{authors.name}}",
            "homepage": "{{authors.url}}"
        },{{array:authors}}
    ]
}