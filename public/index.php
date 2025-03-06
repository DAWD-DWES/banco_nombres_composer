<?php

require_once '../vendor/autoload.php';
include_once '../src/error_handler.php';

use App\bd\BD;
use App\dao\{
    OperacionDAO,
    CuentaDAO,
    ClienteDAO
};
use App\modelo\{
    Banco
};
use App\modelo\TipoCuenta;
use App\modelo\TipoOperacion;
use App\excepciones\SaldoInsuficienteException;

use Faker\Factory;

$faker = Factory::create('es_ES');

$bd = BD::getConexion();

$operacionDAO = new OperacionDAO($bd);
$cuentaDAO = new CuentaDAO($bd, $operacionDAO);
$clienteDAO = new ClienteDAO($bd, $cuentaDAO);

$banco = new Banco($clienteDAO, $cuentaDAO, $operacionDAO, "Midas", [3, 1000], [1.5, 0.5]);

// Datos de clientes de ejemplo
$datosClientes = array_map(fn($x) => ['dni' => $faker->dni(),
    'nombre' => $faker->firstName('male' | 'female'),
    'apellido1' => $faker->lastName(),
    'apellido2' => $faker->lastName(),
    'telefono' => $faker->mobileNumber(),
    'fechaNacimiento' => $faker->date('Y-m-d')], range(0, 9));

// Crear tres clientes y agregar tres cuentas a cada uno
foreach ($datosClientes as $datosCliente) {
    $banco->altaCliente($datosCliente['dni'], $datosCliente['nombre'], $datosCliente['apellido1'], $datosCliente['apellido2'], $datosCliente['telefono'], $datosCliente['fechaNacimiento']);
    // Crear tres cuentas bancarias para cada cliente
    for ($i = 0; $i < 3; $i++) {
        $tipoCuenta = rand(0, 1) ? TipoCuenta::CORRIENTE : TipoCuenta::AHORROS;
        $idCuenta = ($tipoCuenta === TipoCuenta::CORRIENTE) ? $banco->altaCuentaCorrienteCliente($datosCliente['dni']) :
                $banco->altaCuentaAhorrosCliente($datosCliente['dni'], rand(0, 1) ? true : false);
        // Realizar tres operaciones de ingreso en las cada cuenta
        for ($j = 0; $j < 3; $j++) {
            $tipoOperacion = rand(0, 1) ? TipoOperacion::INGRESO : TipoOperacion::DEBITO;
            $cantidad = rand(0, 500);
            try {
                if ($tipoOperacion === TipoOperacion::INGRESO) {
                    $banco->ingresoCuentaCliente($datosCliente['dni'], $idCuenta, $cantidad, "Ingreso de $cantidad € en la cuenta");
                } else {
                    $banco->debitoCuentaCliente($datosCliente['dni'], $idCuenta, $cantidad, "Retirada de $cantidad € en la cuenta");
                }
            } catch (SaldoInsuficienteException $ex) {
                echo $ex->getMessage() . "</br>";
            }
        }
    }
}

try {
    $banco->aplicaComisionCC();
    $banco->aplicaInteresCA();
} catch (SaldoInsuficienteException $ex) {
    echo $ex->getMessage() . "</br>";
}

$clientes = $banco->obtenerClientes();

$dniCliente1 = $clientes[rand(0, count($clientes))]->getDni();
$dniCliente2 = $clientes[rand(0, count($clientes))]->getDni();

try {
    $banco->realizaTransferencia($dniCliente1, $dniCliente2, ($banco->obtenerCliente($dniCliente1)->getIdCuentas())[0], ($banco->obtenerCliente($dniCliente2)->getIdCuentas())[0], 500);
} catch (SaldoInsuficienteException $ex) {
    echo $ex->getMessage();
}

// Mostrar las cuentas y saldos de las cuentas de los clientes
echo "<h1>Clientes y cuentas del banco</h1>";

foreach ($clientes as $dniCliente => $cliente) {
    echo "Datos del cliente con DNI: {$cliente->getDni()} </br>";
    $idCuentas = $cliente->getIdCuentas();
    foreach ($idCuentas as $idCuenta) {
        $cuenta = $banco->obtenerCuenta($idCuenta);
        echo "</br>$cuenta </br>";
    }
    echo "</br>";
}


$dniCliente3 = $clientes[rand(0, count($clientes))]->getDni();
$dniCliente4 = $clientes[rand(0, count($clientes))]->getDni();

$banco->bajaCuentaCliente($dniCliente3, ($banco->obtenerCliente($dniCliente3)->getIdCuentas())[0]);
$banco->bajaCliente($dniCliente4);

// Mostrar las cuentas y saldos de las cuentas de los clientes despues de la baja
echo "<h1>Clientes y cuentas del banco (baja de una cuenta y un cliente)</h1>";
$clientes = $banco->obtenerClientes();
foreach ($clientes as $dniCliente => $cliente) {
    echo "</br> Datos del cliente con DNI: {$cliente->getDni()} </br>";
    $idCuentas = $cliente->getIdCuentas();
    foreach ($idCuentas as $idCuenta) {
        $cuenta = $banco->obtenerCuenta($idCuenta);
        echo "</br>$cuenta</br>";
    }
}