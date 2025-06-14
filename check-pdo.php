<?php
if (extension_loaded('pdo')) {
    echo "PDO est activé<br>";
    echo "Drivers PDO disponibles : <br>";
    print_r(PDO::getAvailableDrivers());
} else {
    echo "PDO n'est pas activé";
}
?> 