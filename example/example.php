<?php

namespace Trinet\EuvatValidation;

require('../src/Validator.php');

$vatCheck = new Validator();
$vatCheck->setVat('AT123456789');
echo '<pre>';
$valid = $vatCheck->check();
if ($valid === true) {
    echo 'valid' . "\n\n";
} else {
    echo 'NOT valid' . "\n\n";
}
echo $vatCheck->getVat() . "\n";
echo $vatCheck->getName() . "\n";
echo $vatCheck->getAddress() . "\n";
if ($vatCheck->getRequestDate() instanceof \DateTime) {
    echo $vatCheck->getRequestDate()->format('d.m.Y') . "\n";
}
echo '</pre>';