<?php

namespace App\modelo;

use App\modelo\Cuenta;
use App\dao\OperacionDAO;


/**
 * Clase CuentaCorriente 
 */
class CuentaCorriente extends Cuenta {

    public function __construct(OperacionDAO $operacionDAO, int $idCliente, float $saldo = 0, string $fechaCreacion = "now") {
        parent::__construct($operacionDAO, $idCliente, TipoCuenta::CORRIENTE, $saldo, $fechaCreacion);
    }
    
    /**
     * 
     * @param type $cantidad Cantidad de dinero a retirar
     * @param type $descripcion Descripcion del debito
     */
    public function debito(float $cantidad, string $descripcion): void {
            $operacion = new Operacion($this->getId(), TipoOperacion::DEBITO, $cantidad, $descripcion);
            $this->operacionDAO->crear($operacion);
            $this->agregaOperacion($operacion);
            $this->setSaldo($this->getSaldo() - $cantidad);
    }

    public function aplicaComision($comision, $minSaldo): void {
        if ($this->getSaldo() < $minSaldo) {
            $this->debito($comision, "Cargo de comisión de mantenimiento");
        }
    }
}
