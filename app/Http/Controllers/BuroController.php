<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;

class BuroController extends Controller
{
    /***********************************************************/
    function getConn() {
        $conn = null;
        try{
            $conn = pg_connect("dbname=giro");
        } catch (Exception $e){
            echo 'Caught exception: ',  $e->getMessage(), "\n";
        }
        return $conn;
    }

    /***********************************************************/
    function consulta($solicitante, $noEtapa, $valorEtapa, $ProductoRequerido, $TipoCuenta,
                      $ClaveUnidadMonetaria, $ImporteContrato, $usuario_id_sol, $usuario_id_aut, $computadora,
                      $confirma, $sucursal ) {
        return consultav2($solicitante, $noEtapa, $valorEtapa, $ProductoRequerido, $TipoCuenta,
            $ClaveUnidadMonetaria,$ImporteContrato,$usuario_id_sol,
            $usuario_id_aut, $computadora, $confirma, $sucursal, null);
    }

    /***********************************************************/
    function consultav2($solicitante, $noEtapa, $valorEtapa, $ProductoRequerido, $TipoCuenta,
                        $ClaveUnidadMonetaria, $ImporteContrato, $usuario_id_sol, $usuario_id_aut, $computadora,
                        $confirma, $sucursal, $buro ) {
        $conn = null;
        $folio = 0;

        try {
            $conn = getConn();

            if ($buro == null) {
                $sql = "SELECT t_persona FROM solicitante WHERE numero = " + $solicitante;
                $result = pg_query($conn, $sql);
                if ($result) {
                    $buro = pg_fetch_result($result, 0, 't_persona');
                }
            }

            if ($buro == "F") {
                // Consulta PF
                echo ( "consultapf put solicitante " + $solicitante );
                $client = new BuroPFImpl();
                $folio = $client.consulta($conn, $solicitante, $noEtapa, $valorEtapa, $ProductoRequerido, $TipoCuenta, $ClaveUnidadMonetaria, $ImporteContrato, $usuario_id_sol, $usuario_id_aut, $computadora, $confirma, $sucursal);
            } else {
                // Consulta PFAE, PM
                echo ( "consultaPM put solicitante " + $solicitante );
                $client = new BuroPMImpl();
                $folio = $client.consulta($conn, $solicitante, $noEtapa, $valorEtapa, $ProductoRequerido, $TipoCuenta, $ClaveUnidadMonetaria, $ImporteContrato, $usuario_id_sol, $usuario_id_aut, $computadora, $confirma, $sucursal);
            }
        } catch (Exception $e) {
            echo $e;
        } finally {
            if ($conn != null) {
                try { pg_close($conn); }
                catch (SQLException $e) { echo $e; }
            }
        }

        return $folio;
    }

    /***********************************************************/
    function respuestaPF($folio) {
        $conn = null;
        $resultado = -1;

        try {
            $conn = getConn();
            $client = new BuroPFImpl();
            $resultado = $client.respuesta($conn, $folio);
        } catch (Exception $e) {
            echo $e;
        } finally {
            if ($conn != null) {
                try { pg_close($conn); }
                catch (SQLException $e) { echo $e; }
            }
        }

        return $resultado;
    }

    /***********************************************************/
    function respuestaPM($folio) {
        $conn = null;
        $resultado = -1;

        try {
            $conn = getConn();
            $client = new BuroPMImpl();
            $resultado = $client.respuesta(getConn(), $folio);

            // Ejecutamos reporte si corresponde
            if ($resultado > 0) {
                $rep = new Reporte();
                if ($rep.consultaBuro($folio))
                    return $rep.getContenidoReporte();
            }
        } catch (Exception $e) {
            echo $e;
        } finally {
            if ($conn != null) {
                try { pg_close($conn); }
                catch (SQLException $e) { echo $e; }
            }
        }

        return null;
    }

    /***********************************************************/
    function reporteConsultaPM($folio) {
        $rep = new Reporte();
        $contenidoReporte = null;

        try {
            if ($folio == "" || ! $this.comprobarFolio($folio))
                return null;

            if ($rep.consultaBuro($folio))
                $contenidoReporte = $rep.getContenidoReporte();
        } catch (Exception $e) {
            echo $e;
        }

        return $contenidoReporte;
    }

    /***********************************************************/
    function autenticador($Solicitante,
                          $ClaveUnidadMonetaria,
                          $ImporteContrato,
                          $usuario_id_sol,
                          $usuario_id_aut,
                          $computadora,
                          $confirma,
                          $sucursal,
                          $sValores) {
        $buro = new BuroPFAutenticador();
        $folio = $buro.consulta(getConn() ,$Solicitante, $ClaveUnidadMonetaria, $ImporteContrato, $usuario_id_sol, $usuario_id_aut, $computadora, $confirma, $sucursal, $sValores);
        return $folio;
    }

}
