<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use App\Http\Controllers\BuroPFImplController;
use App\Http\Controllers\BuroPMImplController;


use DB;
use Response;

class BuroController extends Controller
{
    /*
     * Test dataabse
    */
    function testSql(Request $request) {

        $ret = array();
        $user = DB::table('paises as test')
        //->where('id', 1)
        ->select(DB::raw('test.*'))
        ->first();
        $ret['test'] = $user;
        return Response::json($ret);
    }
    /*
     * test relation ob ject
     */
    function testRelateObject(Request $request) {
        $buuro = new BuroPFImplController();
        return $buuro->testObject();
    }

    function testException(Request $request) {
        try {
            $ret = array();
            $user = DB::table('paises as test_')
                //->where('id', 1)
                ->select(DB::raw('test.*'))
                ->get();
            $ret['test'] = $user;
            return Response::json($ret);
        }
        catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    ////////////test  end/////////////////////

    /*
     * consulta
     */
    public function consulta($solicitante, $noEtapa, $valorEtapa, $ProductoRequerido, $TipoCuenta,
                      $ClaveUnidadMonetaria, $ImporteContrato, $usuario_id_sol, $usuario_id_aut, $computadora,
                      $confirma, $sucursal ) {
        return $this->consultav2($solicitante, $noEtapa, $valorEtapa, $ProductoRequerido, $TipoCuenta,
            $ClaveUnidadMonetaria,$ImporteContrato,$usuario_id_sol,
            $usuario_id_aut, $computadora, $confirma, $sucursal, null);
    }

    /*
     * consultav2
     */
    public function consultav2($solicitante, $noEtapa, $valorEtapa, $ProductoRequerido, $TipoCuenta,
                        $ClaveUnidadMonetaria, $ImporteContrato, $usuario_id_sol, $usuario_id_aut, $computadora,
                        $confirma, $sucursal, $buro ) {

        $folio = 0;
        try {
        $conn = '';
            if ($buro == null) {
                $data = DB::table('solicitante')
                        ->whereRaw("numero = " + $solicitante)
                        ->select(DB::raw('*'))
                        ->first();
                if(!empty($data)) {
                    $buro = $data->t_persona;
                }
            }

            if ($buro == "F") {
                // Consulta PF
                //echo ( "consultapf put solicitante " + $solicitante );
                $client = new BuroPFImplController();
                $folio = $client->consulta($conn, $solicitante, $noEtapa, $valorEtapa, $ProductoRequerido, $TipoCuenta, $ClaveUnidadMonetaria, $ImporteContrato, $usuario_id_sol, $usuario_id_aut, $computadora, $confirma, $sucursal);
            } else {
                // Consulta PFAE, PM
                //echo ( "consultaPM put solicitante " + $solicitante );
                $client = new BuroPFImplController();
                $folio = $client->consulta($conn, $solicitante, $noEtapa, $valorEtapa, $ProductoRequerido, $TipoCuenta, $ClaveUnidadMonetaria, $ImporteContrato, $usuario_id_sol, $usuario_id_aut, $computadora, $confirma, $sucursal);
            }
        }
        catch (\Exception $e) {
            return $e->getMessage();
        }
        return $folio;
    }

    /*
     * respuestaPF
     */
    public function respuestaPF($folio) {

        $resultado = -1;
        $conn = '';
        try {
            $client = new BuroPFImplController();
            $resultado = $client->respuesta($conn, $folio);
        }
        catch (\Exception $e) {
                return $e->getMessage();
            }
        return $resultado;
    }

    /*
     * respuestaPM
     */
    public function respuestaPM($folio) {
        $conn = '';
        $resultado = -1;
        try {
            $client = new BuroPMImplController();
            $resultado = $client->respuesta(conn, $folio);

            // Ejecutamos reporte si corresponde
            if ($resultado > 0) {
                $rep = new Reporte();
                if ($rep->consultaBuro($folio))
                    return $rep->getContenidoReporte();
            }
        }catch (\Exception $e) {
                return $e->getMessage();
        }
        return null;
    }

    /*
     * reporteConsultaPM
     *
     */
    public function reporteConsultaPM($folio) {
        $rep = new Reporte();
        $contenidoReporte = null; //byte[] contenidoReporte = null;
        try {
            if ($folio == "" || !$this->comprobarFolio($folio))
                return null;

            if ($rep->consultaBuro($folio))
                $contenidoReporte = $rep->getContenidoReporte();
        }catch (\Exception $e) {
                return $e->getMessage();
        }

        return $contenidoReporte;
    }

    /*
     * autenticador
     */
    public function autenticador($Solicitante,
                          $ClaveUnidadMonetaria,
                          $ImporteContrato,
                          $usuario_id_sol,
                          $usuario_id_aut,
                          $computadora,
                          $confirma,
                          $sucursal,
                          $sValores) {
        $buro = new BuroPFAutenticador();
        $folio = $buro->consulta(getConn() ,$Solicitante, $ClaveUnidadMonetaria, $ImporteContrato, $usuario_id_sol, $usuario_id_aut, $computadora, $confirma, $sucursal, $sValores);
        return $folio;
    }

    /*
     * califica
     */

    public function califica($iFolio) {
        $conn = '';
        $resultado = "";
        $iResult = 0;
        try {
            $conn = "";
            //get table from variable iFolio
            $res2 = DB::select("select buro_califica_cuentas(" + iFolio + ")");
            $iResult = reset($res2); //get first colum 

            if (iResult > 0){
                $res2 = DB::select("select coalesce(autorizar,'') from cat_resultado where resultado_id = " + $iResult);
                if (empty($res2))
                    return -1;
                $resultado = reset($res2); // get first colum
                if ($resultado == "S") //if ($resultado.compareTo("S") == 0)
                    return 1;
                else {
                    if ($resultado == "A") //if (resultado.compareTo("A") == 0)
                        return 2;
                    else
                        return 0;
                }
            } else
                return -1;
        } catch (\Exception $e) {
            return $e->getMessage();
        }

        return -1;
    }

    /*
     * comprobarFolio
     */
	private function comprobarFolio($folio) {
        try {
            $res = DB::select("SELECT COUNT(*) AS existe FROM consultas_circulo WHERE folioconsulta = '" + $folio + "'");

            if (!empty($res)) return false;

            return ($res->existe > 0);
        } catch (\Exception $e) {
            return $e->getMessage();
        }
	}

}
