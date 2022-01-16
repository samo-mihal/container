<?php

return [
    'container:integrity' => [
        'class' => \B13\Container\Command\IntegrityCommand::class,
    ],
    'container:deleteChildrenWithWrongPid' => [
        'class' => \B13\Container\Command\DeleteChildrenWithWrongPidCommand::class,
    ],
    'integrity:run' => [
        'class' => \B13\Container\Command\IntegrityCommand::class,
    ]
];
