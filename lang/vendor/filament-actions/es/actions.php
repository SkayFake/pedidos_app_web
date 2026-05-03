<?php

return [
    'confirmation' => [
        'heading' => 'Confirmación',
        'description' => '¿Estás seguro de que deseas realizar esta acción?',
    ],
    'modal' => [
        'confirmation' => '¿Estás seguro?',
        'heading' => 'Confirmar acción',
        'actions' => [
            'cancel' => [
                'label' => 'Cancelar',
            ],
            'confirm' => [
                'label' => 'Confirmar',
            ],
            'submit' => [
                'label' => 'Enviar',
            ],
        ],
    ],
    'notifications' => [
        'replicated' => [
            'title' => 'Registro duplicado',
        ],
    ],
    'create' => [
        'single' => [
            'label' => 'Crear',
            'modal' => [
                'heading' => 'Crear :label',
                'actions' => [
                    'create' => [
                        'label' => 'Crear',
                    ],
                    'create_another' => [
                        'label' => 'Crear y crear otro',
                    ],
                ],
            ],
            'notifications' => [
                'created' => [
                    'title' => 'Registro creado',
                ],
            ],
        ],
    ],
    'delete' => [
        'single' => [
            'label' => 'Eliminar',
            'modal' => [
                'heading' => 'Eliminar :label',
                'description' => '¿Estás seguro de que deseas eliminar este registro? Esta acción no se puede deshacer.',
                'actions' => [
                    'delete' => [
                        'label' => 'Eliminar',
                    ],
                ],
            ],
            'notifications' => [
                'deleted' => [
                    'title' => 'Registro eliminado',
                ],
            ],
        ],
        'multiple' => [
            'label' => 'Eliminar seleccionados',
            'modal' => [
                'heading' => 'Eliminar :label seleccionados',
                'description' => '¿Estás seguro de que deseas eliminar los registros seleccionados? Esta acción no se puede deshacer.',
                'actions' => [
                    'delete' => [
                        'label' => 'Eliminar',
                    ],
                ],
            ],
            'notifications' => [
                'deleted' => [
                    'title' => 'Registros eliminados',
                ],
            ],
        ],
    ],
    'edit' => [
        'single' => [
            'label' => 'Editar',
            'modal' => [
                'heading' => 'Editar :label',
                'actions' => [
                    'save' => [
                        'label' => 'Guardar cambios',
                    ],
                ],
            ],
            'notifications' => [
                'saved' => [
                    'title' => 'Registro actualizado',
                ],
            ],
        ],
    ],
    'view' => [
        'single' => [
            'label' => 'Ver',
            'modal' => [
                'heading' => 'Ver :label',
                'actions' => [
                    'close' => [
                        'label' => 'Cerrar',
                    ],
                ],
            ],
        ],
    ],
    'restore' => [
        'single' => [
            'label' => 'Restaurar',
            'modal' => [
                'heading' => 'Restaurar :label',
                'description' => '¿Estás seguro de que deseas restaurar este registro?',
                'actions' => [
                    'restore' => [
                        'label' => 'Restaurar',
                    ],
                ],
            ],
            'notifications' => [
                'restored' => [
                    'title' => 'Registro restaurado',
                ],
            ],
        ],
        'multiple' => [
            'label' => 'Restaurar seleccionados',
            'modal' => [
                'heading' => 'Restaurar :label seleccionados',
                'description' => '¿Estás seguro de que deseas restaurar los registros seleccionados?',
                'actions' => [
                    'restore' => [
                        'label' => 'Restaurar',
                    ],
                ],
            ],
            'notifications' => [
                'restored' => [
                    'title' => 'Registros restaurados',
                ],
            ],
        ],
    ],
    'force_delete' => [
        'single' => [
            'label' => 'Eliminar permanentemente',
            'modal' => [
                'heading' => 'Eliminar permanentemente :label',
                'description' => '¿Estás seguro de que deseas eliminar permanentemente este registro?',
                'actions' => [
                    'force_delete' => [
                        'label' => 'Eliminar permanentemente',
                    ],
                ],
            ],
            'notifications' => [
                'force_deleted' => [
                    'title' => 'Registro eliminado permanentemente',
                ],
            ],
        ],
        'multiple' => [
            'label' => 'Eliminar permanentemente seleccionados',
            'modal' => [
                'heading' => 'Eliminar permanentemente :label seleccionados',
                'description' => '¿Estás seguro de que deseas eliminar permanentemente los registros seleccionados?',
                'actions' => [
                    'force_delete' => [
                        'label' => 'Eliminar permanentemente',
                    ],
                ],
            ],
            'notifications' => [
                'force_deleted' => [
                    'title' => 'Registros eliminados permanentemente',
                ],
            ],
        ],
    ],
    'replicate' => [
        'single' => [
            'label' => 'Duplicar',
            'modal' => [
                'heading' => 'Duplicar :label',
                'actions' => [
                    'replicate' => [
                        'label' => 'Duplicar',
                    ],
                ],
            ],
            'notifications' => [
                'replicated' => [
                    'title' => 'Registro duplicado',
                ],
            ],
        ],
    ],
];
