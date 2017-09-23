<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Faker\Provider\zh_TW\DateTime;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;


use DB;
use Response;
use Log;

class INTFLecturaController extends Controller
{
    private $st = null;

    public  function respuesta($conn , $sfolio) {
        // TODO Auto-generated method stub
        $idr = 0;        
        //@SuppressWarnings("unused")
        $sINTL = "";
        $sNombre = "";
        $sDireccion = "";
        $sEmpleo = "";
        $sCuentas = "";
        $xEtiq = "";
        $longitud = 0;
        $Error="";
        $Err="";
        $sTexto="";
        $sValorn = "select respuestaxml from consultas_circulo where folioconsulta = '" . $sfolio . "'";
        try{
            Log::info("try");

            $sInsert = "Delete from circulo_personas where folioconsultaotorgante = " . $sfolio;
            $st = DB::delete($sInsert);
            $sInsert = "Delete from circulo_domicilios where folioconsultaotorgante = " . $sfolio;
            $st = DB::delete($sInsert);
            $sInsert = "Delete from circulo_cuentas where folioconsultaotorgante = " . $sfolio;
            $st = DB::delete($sInsert);
            $sInsert = "Delete from circulo_empleos where folioconsultaotorgante = " . $sfolio;
            $st = DB::delete($sInsert);
            $sInsert = "Delete from circulo_consultas_efectuadas  where folioconsultaotorgante = " .sfolio;
            $st = DB::delete($sInsert);
            $res2 = DB::select($sValorn);
            if(!empty($res2))
                    $sTexto = reset($res2);

                
            $par = (int)$sfolio;
                
            if($sTexto != "")
            {
                    $b1 = 0;
                    $longitud = strlen($sTexto);
                
                if ($longitud > 0) {
                        $sINTL = substr($sTexto , 47,48);
                        if ($longitud < 382) {
                                $sNombre = substr($sTexto , 49 , $longitud);	//NOMBRE
                }
                else {
                    $sNombre = substr($sTexto , 49, 384);	//NOMBRE
                }
            
            $b1 = $this->lee_nombre($sNombre, $par);
            if($b1 < 0)
            {
                $Error = "NOMBRE";
                $longitud = -1;
            }
            
            $b1 += 49;
            
            if ($b1 < $longitud) {
                do {
                    if ($longitud < ($b1+313)) {
                        $sDireccion = substr($sTexto , $b1, $longitud);
                    }
                    else {
                        
                        $sDireccion = substr($sTexto , $b1 , $b1 + 315);                        
                    }
                    $b1 .= $this->lee_dir($sDireccion, $par);
                    $xEtiq = substr($sTexto , $b1 , $b1 + 2);
                } while ($xEtiq  == "PA" && $b1 != -1);
                if($b1 < 0)
                {
                    $Error .= ", DIRECCION";
                    $idr = $longitud = -1;
                }
            }
            
            if ($b1 < $longitud) {
                
                do {
                    if ($longitud < ($b1 + 453)) {
                        $sEmpleo = substr($sTexto , $b1 , $longitud);
                    }
                    else {
                        if(($b1+545) <= $longitud )
                        {
                            $sEmpleo = substr($sTexto , $b1 , $b1 + 545);
                        }
                        else
                        {
                            $sEmpleo = substr($sTexto , $b1, $longitud);
                        }
                        
                    }
                    $b1 .= $this->lee_empleo($sEmpleo, $par);
                    $xEtiq = substr($sTexto , $b1, $b1 + 2);
                } while ($xEtiq  == "PE" && $b1 != -1);
                if($b1 < 0)
                {
                    $Error = ", EMPLEO";
                    $idr = $longitud += -1;
                }
                
            }
            
            if ($b1 < $longitud) {
                
                do {
                    if ($longitud < ($b1 + 478)) {
                        
                        $sCuentas = substr($sTexto.substring($b1 , $longitud));
                    }
                    else {
                        
                        if 	($longitud < ($b1 + 480))
                            $sCuentas = substr($sTexto , $b1, $longitud);
                        else
                            $sCuentas = substr($sTexto , $b1 , $b1 + 480);
                        
                    }
                    
                    $b1 .= $this->lee_cuentas($sCuentas, $par);
                    
                    $xEtiq = substr($sTexto , $b1 , $b1+2);
                    if ($xEtiq != "TL" && $xEtiq != "IQ"  && $xEtiq != "RS") {
                        $Error .= ", CUENTAS ";
                        $idr = $longitud = -1;
                    }
                    
                } while ($xEtiq == "TL" && $b1 != -1);
                if($b1 < 0)
                {
                    $Error .= ", CUENTAS";
                    $idr = $longitud = -1;
                }
            }

                if ($b1 < $longitud) {
                    do {
                        if ($longitud < ($b1 + 133)) {
                            $sCuentas = substr($sTexto , $b1 , $longitud);
                            
                        }
                        else {
                            $sCuentas = subtr($sTexto , $b1, $b1 + 135);                        
                        }
                        $b1 .= $this->lee_consultas($sCuentas, $par);
                        $xEtiq = substr($sTexto , $b1, $b1 + 2);
                    } while ($xEtiq  == "IQ" && $b1 != -1);
                    if($b1 < 0)
                    {
                        $Error = ", CONSULTAS";
                        $idr = $longitud = -1;
                    }
                }
            }
                if($idr==-1)
                {
                    $Err = "ERROR DE INTERPRETACION DE SEGMENTO(S): " . $Error;
                }
            }
        } catch(\Exception $e) {
             Logger::info($e);
            		$idr =-1;
        			$Err .= $e;
        		}
        		if($idr == -1)
                {
                    $sInsert = "Update consultas_circulo set error = '" . $Err . "' || coalesce(error,'') , fecha_creacion = CURRENT_TIMESTAMP where folioconsulta = " . $sfolio;
        		    try{
                        $st = DB::insert($sInsert);
                        
                        
                    } catch(\Exception $e) {
                      Log::info($e);
        			}
        		}
                else
                {
                    Log::info($sValorn);
                    $numc = $this->lee_fin($sTexto);
        			$sInsert2 = "Update consultas_circulo set error='', control = '" . $numc . "' , cuenta_buro = ". numc .
                    " , fecha_creacion = CURRENT_TIMESTAMP where folioconsulta = " . sfolio ;
        			try{
                        $st = DB::update($sInsert2);
                        
                    }
                    catch(\Exception $e){
                        Log::info($e);
        			}

        			$sInsert2 = "select buro_califica_cuentas(" . $sfolio .")";
        			try{
                        $st = DB::select($sInsert2);                        
                    }
                    catch(\Exception $e){
                      Log::info($e);
        			}
        		}
        		
        		return $idr     ;
    	}


	public  function lee_nombre($pNombre, $fol) {
        $sPropio = "";
		$sSegundo = "";
		$sAdicional = "";
		$sPaterno = "";
		$sMaterno = "";
		$sFecha = "";
		$sRFC = "";
		$sPrefijo = "";
		$sSufijo = "";
		$sNac = "";
		$sRef = "";
		$sLic = "";
		$sEdoCivil = "";
		$sSexo = "";
		$sCedula  = "";
		$sElectoral = "";
		$sImpuesto = "";
		$sPais = "";
		$sDep = "0";
		$sEdades = "";
		$sInforma = "";
		$sDef = "";
		$sEtiq = "";
		$sLong = "";
		$iPos = 0;
		$iLong = 0;
		$fFecNac = new DateTime(0,9,9); //= new Date();
		$xLong = strlen($pNombre);
		while ($iPos < $xLong) {
            $sEtiq = substr($pNombre, $iPos, $iPos + 2);
            $iPos = $iPos + 4;
            $sLong = substr($pNombre, $iPos-2, $iPos);
            $iLong = (int)$sLong;
            if ($sEtiq  == "PN") {
                $sPaterno = substr($pNombre, $iPos, $iPos + $iLong);  //Apellido Paterno
            }
            if ($sEtiq == "00") {
                $sMaterno = substr($pNombre , $iPos , $iPos + $iLong);  //Apellido Materno
            }
            if ($sEtiq  == "01") {
                $sAdicional = substr($pNombre , $iPos, $iPos + $iLong);  //Apellido Adicional
            }
            if ($sEtiq == "02") {
                $sPropio = substr($pNombre , $iPos, $iPos + $iLong);  //Nombre Propio
            }
            if ($sEtiq == "03") {
                $sSegundo = substr($pNombre , $iPos, $iPos + $iLong);  //Segund Nombre
            }
            if ($sEtiq  == "04") {
                $sFecha = substr($pNombre, $iPos, $iPos + $iLong); 	  //Fec Nacimiento
                $fFecNac = $this->convierte_fecha($sFecha);
            }
            if ($sEtiq  == "05") {
                $sRFC = substr($pNombre , $iPos , $iPos + $iLong); 	  //RFC
            }
            if ($sEtiq  == "08") {
                $sNac = substr($pNombre , $iPos, $iPos + $iLong); 	  //Nacionalidad
            }
            if ($sEtiq  == "11") {
                $sEdoCivil = substr($pNombre , $iPos , $iPos + $iLong); 	  //Estado Civil
            }

            if ($sEtiq  == "12") {
                $sSexo = substr($pNombre , $iPos, $iPos + $iLong); 	  //Sexo
            }
            if ($sEtiq  == "14") {
                $sElectoral = substr($pNombre , $iPos, $iPos + $iLong); 	  //IFE
            }
            if ($sEtiq  == "17") {
                $sDep =  substr($pNombre , $iPos, $iPos + $iLong); 	  //Dependientes
            }
            if (($sEtiq  == "PA") || ($sEtiq =="PE") || ($sEtiq == "TL") || ($sEtiq == "IQ")  || ($sEtiq == "RS"))  {
                $iLong = $iPos - 4;
                $iPos = $xLong;
            }
            $iPos += $iLong;
        }
		$sInsert = "Insert into circulo_personas(folioconsultaotorgante, nombres, apellidopaterno, apellidomaterno, apellidoadicional, " .
        "fechanacimiento, rfc, nacionalidad, estadocivil, numerodependientes, sexo, claveife) values(" .
        $fol . ",'" . $sPropio . " " . $sSegundo . "','" .  $sPaterno . "','" . $sMaterno . "','" . $sAdicional . "','" . $fFecNac . "','" . $sRFC . "','" .
        $sNac . "','" . $sEdoCivil . "'," . sDep . ",'" . sSexo ."','" . sElectoral ."')";
		try{
            $st = DB::insert($sInsert);            
        }
        catch(\Exception $e)
		{
            Log::info($e);
			$iLong = -1;
		}
		return $iLong;
	}

	public function lee_dir($pDir, $fol) {
        $sDir1 = "";
		$sDir2 = "";
		$sCol = "";
		$sMun = "";
		$sCid = "";
		$sEdo = "";
		$sCP = "0";
		$sFecRes = "";
		$sTel = "";
		$sFecDom = "";
		$sEtiq = "";
		$sLong = "";
		$fFecRes = new DateTime(0,9,9);
		$fFecDom = new DateTime(0,9,9);
		$iPos = 0;
		$iLong = 0;
		$xLong = strlen($pDir);
		while ($iPos < $xLong) {
            $sEtiq = substr($pDir , $iPos, $iPos + 2);
            $iPos = $iPos + 4;
            $sLong = substr($pDir , $iPos-2 , $iPos);
            $iLong = (int)$sLong;
            if ($sEtiq == "PA") {
                if ($iPos == 4) {
                    $sDir1 = substr($pDir , $iPos , $iPos + $iLong);  //Domicilio 1
                    $sEtiq = "";
                }
            }
            if ($sEtiq  == "00") {
                $sDir2 = substr($pDir , $iPos , $iPos + $iLong);  //Domicilio 2
            }
            if ($sEtiq  == "01") {
                $sCol = substr($pDir , $iPos, $iPos + $iLong);  //Colonia
            }
            if ($sEtiq  == "02") {
                $sMun = substr($pDir  , $iPos, $iPos + $iLong);  //Municipio
            }
            if ($sEtiq == "03") {
                $sCid = substr($pDir , $iPos, $iPos + $iLong);  //Ciudad
            }
            if ($sEtiq  == "04") {
                $sEdo = substr($pDir , $iPos , $iPos + $iLong);  //Estado
            }
            if ($sEtiq  == "05") {
                $sCP = substr($pDir , $iPos , $iPos + $iLong);  //Codigo postal
            }
            if ($sEtiq  == "06") {
                $sFecRes = substr($pDir , $iPos, $iPos + $iLong); 	  //Fecha Residencia
                $fFecRes = $this->convierte_fecha($sFecRes);
            }
            if ($sEtiq  == "07") {
                $sTel = substr(pDir , $iPos , $iPos + $iLong); 	  //Telefono
            }
            if ($sEtiq  == "12") {
                $sFecDom = substr($pDir , $iPos, $iPos + $iLong); 	  //Fecha Residencia
                $fFecDom = $this->convierte_fecha($sFecDom);
            }
            if (($sEtiq  == "PA") || ($sEtiq  == "PE") || ($sEtiq  == "TL") || ($sEtiq  == "IQ")  || ($sEtiq  == "RS"))  {
                $iLong = $iPos - 4;
                $iPos = $xLong;
            }
            $iPos .= $iLong;
        }

		$sInsert = "Insert into circulo_domicilios(folioconsultaotorgante, direccion, ciudad, estado, cp, fecharesidencia, telefono, delegacionmunicipio, coloniapoblacion, fecharegistrodomicilio) values(" .
        $fol . ",'" . ($sDir1 . " " . $sDir2) . "','" . $sCid . "','" . $sEdo . "'," . $sCP . ",'" . $fFecRes . "','" . $sTel . "','" . $sMun . "','" . sCol . "','" .$fFecDom . "')";

		try{
            $st = DB::insert($sInsert);
            
        } catch(\Exception $e) {
            Log::info($e);
            $iLong = -1;
		}
		return $iLong;
	}

	public function lee_empleo($pEmp, $fol) {
        $sEmpleo = "";
		$sPuesto = "";
		$sDir1 = "";
		$sDir2 = "";
		$sCol = "";
		$sMun = "";
		$sCid = "";
		$sEdo = "";
		$sCP = "0";
		$sTel = "";
		$sSalario ="0";
		$sFecCon = "01012000";
		$sFecEmp = "01012000";
		$sEtiq = "";
		$sLong = "";
		$fFecCon = new DateTime(0,9,9);
		$fFecEmp = new DateTime(0,9,9);
		$iPos = 0;
		$iLong = 0;
		$xLong = strlen($pEmp);

		$sEtiq = substr($pEmp , $iPos, $iPos+2);
		if ($sEtiq  == "PE") {
            while ($iPos < $xLong) {
                $sEtiq = substr($pEmp , $iPos, $iPos+2);
                $iPos = $iPos + 4;
                $sLong = substr($pEmp , $iPos-2, $iPos);
                $iLong = (int)$sLong;
                if ($sEtiq  == "PE") {
                    if ($iPos == 4) {
                        $sEmpleo = substr($pEmp , $iPos, $iPos + $iLong);  //Nombre Empleo
                        $sEtiq = "";
                    }
                }
                if ($sEtiq  == "00") {
                    $sDir1 = substr($pEmp  , $iPos, $iPos + $iLong);  //Domicilio 1
                }
                if ($sEtiq  == "01") {
                    $sDir2 = substr($pEmp  , $iPos , $iPos + $iLong);  //Domicilio 2
                }
                if ($sEtiq == "02") {
                    $sCol = substr($pEmp  , $iPos, $iPos + $iLong);  //Colonia
                }
                if ($sEtiq  == "03") {
                    $sMun = substr($pEmp , $iPos, $iPos + $iLong);  //Municipio
                }
                if ($sEtiq  == "04") {
                    $sCid = subtr($pEmp , $iPos , $iPos + $iLong);  //Ciudad
                }
                if ($sEtiq  == "05") {
                    $sEdo = substr($pEmp , $iPos , $iPos + $iLong);  //Estado
                }
                if ($sEtiq  == "06") {
                    $sCP = substr($pEmp  , $iPos , $iPos + $iLong);  //Codigo postal
                }
                if ($sEtiq  == "07") {
                    $sTel = substr($pEmp , $iPos , $iPos + $iLong); 	  //Telefono
                }
                if ($sEtiq  == "10") {
                    $sPuesto = substr($pEmp  , $iPos , $iPos + $iLong); 	  //Puesto
                }
                if ($sEtiq  == "11") {
                    $sFecCon = substr($pEmp  ,$iPos, $iPos + $iLong);	//Fecha Contracion
                    $fFecCon = $this->convierte_fecha($sFecCon);
                }
                if ($sEtiq  == "13") {
                    $sSalario = substr($pEmp , $iPos, $iPos + $iLong); 	  //Salario
                }
                if ($sEtiq  == "16") {
                    $sFecEmp = substr($pEmp , $iPos, $iPos+ $iLong);	//Fecha Ultimo dia
                    $fFecEmp = $this->convierte_fecha($sFecEmp);
                }
                if (($sEtiq  == "PE") || ($sEtiq == "TL") || ($sEtiq == "IQ")  || ($sEtiq  == "RS"))  {
                    $iLong = $iPos - 4;
                    $iPos = $xLong;
                }
                $iPos += $iLong;
            }
            $sInsert = "Insert into circulo_empleos(folioconsultaotorgante, direccion, coloniapoblacion, delegacionmunicipio, ciudad, estado, telefono, cp, nombreempresa, puesto, salariomensual, fechacontratacion, fechaultimadiaempleo) values(" .
                $fol . ",'" . ($sDir1 . " " . $sDir2) . "','" . $sCol . "','" . $sMun . "','" . $sCid . "','" . $sEdo . "','" . $sTel . "'," . $sCP . ",'" . $sEmpleo . "','" . $sPuesto . "'," . $sSalario . ",'" . $fFecCon . "','" . $fFecEmp .  "')";
		try{
            $st = DB::insert($sInsert);            
        } catch(\Exception $e) {
                Log::info($e);
			$iLong = -1;
		}
		}
        else
        {
            $fFecCon = $this->convierte_fecha($sFecCon);
            $fFecEmp = $this->convierte_fecha($sFecEmp);

            $sInsert = "Insert into circulo_empleos(folioconsultaotorgante, direccion, coloniapoblacion, delegacionmunicipio, ciudad, estado, telefono, cp, nombreempresa, puesto, salariomensual, fechacontratacion, fechaultimadiaempleo) values(" .
            $fol . ",'" . ($sDir1 . " " . $sDir2) . "','" . $sCol . "','" . $sMun . "','" . $sCid . "','" . $sEdo . "','" . $sTel . "'," . $sCP . ",'" . $sEmpleo . "','" . $sPuesto . "'," . $sSalario . ",'" . $fFecCon . "','" . $fFecEmp .  "')";
			try{
                $st = DB::insert($sInsert);
                
            } catch(\Exception $e) {
               Log::info($e);
				$iLong = -1;
			}
			$iLong = 0;
		}
		return $iLong;
	}


	public function lee_cuentas($pCuenta, $fol) {
        $sFecAct = "";
		$sTContrato = "";
		$sImp = "";
		$clOtor = "";
		$nomOtor = "";
		$sLimite = "";
		$sCuenta = "";
		$sResp = "";
		$sTipo = "";
		$sContrato = "";
		$sMoneda = "";
		$sValuacion = "";
		$sNumPagos = "0";
		$sFrecPagos = "";
		$sFecPeor = "";
		$sFecAper = "";
		$sFecUlt = "";
		$sFecUltc = "";
		$sFecComp = "";
		$sFecCierre = "";
		$sFecRep = "";
		$sFecRec = "";
		$sFecAnt = "";
		$sSaldo = "0";
		$sCremax = "0"; //Credito maximo
		$sSaldov = "0";
		$sMonto = "0";
		$telOtor = "";
		$sVencido = "0";
		$sNumVenc = "0";
		$sMOP = "";
		$sObs = "";
		$sMOP2 = "";
		$sEtiq = "";
		$sLong = "";
		$sHistorial ="";
		$fFecAct = new DateTime('2000-9-9');
		$fFecAper = new DateTime('2000-9-9');
		$fFecUlt = new DateTime('2000-9-9');
		$fFecUltc = new DateTime('2000-9-9');
		$fFecComp = new DateTime('2000-9-9');
		$fFecCierre = new DateTime('2000-9-9');
		$fFecRep = new DateTime('2000-9-9');
		$fFecPeor = new DateTime('2000-9-9');
		$fFecRec = new DateTime('2000-9-9');
		$fFecAnt = new DateTime('2000-9-9');
		$iPos = 0;
		$iLong = 0;
		$xLong = strlen($pCuenta);
		
		while (($iPos+2) < $xLong) {
            $sEtiq = substr($pCuenta , $iPos, $iPos+2);
            
            $iPos = $iPos + 4;
            $sLong = substr($pCuenta, $iPos-2,$iPos);
            $iLong = (int)$sLong;
            if ($sEtiq  == "TL") {
                if ($iPos == 4) {
                    $sFecAct = substr($pCuenta ,$iPos, $iPos+ $iLong);  //Fecha de Actualizacion
                    $fFecAct = $this->convierte_fecha($sFecAct);
                    $sEtiq = "";
                }
            }
            if ($sEtiq == "00") {
                $sImp = substr($pCuenta ,$iPos,$iPos + $iLong);  	//Impugnado por el cliente
            }
            if ($sEtiq  == "01") {
                $clOtor = substr($pCuenta  ,$iPos, $iPos + $iLong);  	//Clave Otorgante
            }
            if ($sEtiq == "02") {
                $nomOtor = substr($pCuenta, $iPos, $iPos + $iLong);  //Nombre Otorgante
            }
            if ($sEtiq == "03") {
                $telOtor = substr($pCuenta ,$iPos, $iPos + $iLong);  //Telefono Otorgante
            }
            if ($sEtiq  == "04") {
                $sCuenta = substr($pCuenta ,$iPos, $iPos + $iLong);  	//Cuenta
            }
            if ($sEtiq  == "05") {
                $sResp = substr($pCuenta, $iPos, $iPos + $iLong);  	//Tipo de Responsabilidad
                if($sResp  == "I")
                    $sResp = "INDIVIDUAL";
                if($sResp == "J")
                    $sResp = "MANCOMUNADO";
                if($sResp == ("C"))
                    $sResp = "OBLIGADO SOLIDARIO";
            }
            if ($sEtiq == ("06")) {
                $sTipo = pCuenta.substring(iPos,iPos+iLong);  	//Tipo de Cuenta
                if($sTipo == ("I"))
                    $sTipo = "PAGOS FIJOS";
                if($sTipo == ("M"))
                    $sTipo = "HIPOTECA";
                if($sTipo == ("O"))
                    $sTipo = "SIN LIMITE ESTABLECIDO";
                if($sTipo == ("R"))
                    $sTipo = "REVOLVENTE";
                Log::info($sTipo . " " . strlen($sTipo));
            }
            if ($sEtiq == ("07")) {
                $sContrato = pCuenta.substring(iPos,iPos+iLong);	//Tipo de contrato
                $sTContrato = $sContrato;
                if($sContrato == ("AF"))
                    $sContrato="APARATOS/MUEBLES";
                if($sContrato == ("AG"))
                    $sContrato="AGROPECUARIO (PFAE)";
                if($sContrato == ("AL"))
                    $sContrato="ARRENDAMIENTO AUTOMOTRIZ";
                if($sContrato == ("AP"))
                    $sContrato="AVIACION";
                if($sContrato == ("AU"))
                    $sContrato="COMPRA DE AUTOMOVIL";
                if($sContrato == ("BD"))
                    $sContrato="FIANZA";
                if($sContrato == ("BT"))
                    $sContrato="BOTE/LANCHA";
                if($sContrato == ("CC"))
                    $sContrato="TARJETA DE CREDITO";
                if($sContrato == ("CE"))
                    $sContrato="CARTAS DE CREDITO (PFAE)";
                if($sContrato == ("CF"))
                    $sContrato="CREDITO FISCAL";
                if($sContrato == ("CL"))
                    $sContrato="LINEA DE CREDITO";
                if($sContrato == ("CO"))
                    $sContrato="CONSOLIDACION";
                if($sContrato == ("CS"))
                    $sContrato="CREDITO SIMPLE (PFAE)";
                if($sContrato == ("CT"))
                    $sContrato="CON COLATERAL";
                if($sContrato == ("DE"))
                    $sContrato="DESCUENTOS (PFAE)";
                if($sContrato == ("EQ"))
                    $sContrato="EQUIPO";
                if($sContrato == ("FI"))
                    $sContrato="FIDEICOMISOS (PFAE)";
                if($sContrato == ("FT"))
                    $sContrato="FACTORAJE";
                if($sContrato == ("HA"))
                    $sContrato="HABILITACION O AVIO (PFAE)";
                if($sContrato == ("HE"))
                    $sContrato="PRESTAMO TIPO *HOME EQUITY*";
                if($sContrato == ("HI"))
                    $sContrato="MEJORAS A LA CASA";
                if($sContrato == ("LS"))
                    $sContrato="ARRENDAMIENTO";
                if($sContrato == ("MI"))
                    $sContrato="OTROS";
                if($sContrato == ("OA"))
                    $sContrato="OTROS ADEUDOS VENCIDOS (PFAE)";
                if($sContrato == ("PA"))
                    $sContrato="PRESTAMOS PARA PERSONAS FISICAS CON ACTIVIDAD EMPRESARIAL PFAE)";
                if($sContrato == ("PB"))
                    $sContrato="EDITORIAL";
                if($sContrato == ("PG"))
                    $sContrato="PGUE (PRESTAMO CON GARANTIAS DE UNIDADES INDUSTRIALES)(PFAE)";
                if($sContrato == ("PL"))
                    $sContrato="PRESTAMO PERSONAL";
                if($sContrato == ("PR"))
                    $sContrato="PRENDARIO (PFAE)";
                if($sContrato == ("PQ"))
                    $sContrato="QUIROGRAFARIO (PFAE)";
                if($sContrato == ("RC"))
                    $sContrato="REESTRUCTURADO";
                if($sContrato == ("RD"))
                    $sContrato="REDESCUENTO (PFAE)";
                if($sContrato == ("RE"))
                    $sContrato="BIENES RAICES";
                if($sContrato == ("RF"))
                    $sContrato="REFACCIONARIO (PFAE)";
                if($sContrato == ("RN"))
                    $sContrato="RENOVADO (PFAE)";
                if($sContrato == ("RV"))
                    $sContrato="VEHICULO RECREATIVO";
                if($sContrato == ("SC"))
                    $sContrato="TARJETA GARANTIZADA";
                if($sContrato == ("SE"))
                    $sContrato="PRESTAMO GARANTIZADO";
                if($sContrato == ("SG"))
                    $sContrato="SEGUROS";
                if($sContrato == ("SM"))
                    $sContrato="SEGUNDA HIPOTECA";
                if($sContrato == ("ST"))
                    $sContrato="PRESTAMO PARA ESTUDIANTE";
                if($sContrato == ("TE"))
                    $sContrato="TARJETA DE CREDITO EMPRESARIAL";
                if($sContrato == ("UK"))
                    $sContrato="DESCONOCIDO";
                if($sContrato == ("US"))
                    $sContrato="PRESTAMO NO GARANTIZADO";
            }
             
            if(strcasecmp($sEtiq ,"08") == 0){ 
                $sMoneda = substr($pCuenta , $iPos,$iPos + $iLong);  //Moneda
                if($sMoneda != "MX" || $sMoneda != "US" || $sMoneda != "UD")
                    $sMoneda ="MX";
            }
            if ($sEtiq == ("10")) {
                $sNumPagos = substr($pCuenta, $iPos,$iPos+$iLong);	//Numero de pagos
            }
            if ($sEtiq == ("11")) {
                $sFrecPagos = substr($pCuenta , $iPos,$iPos+$iLong); //Fecuencia de pagos
                if($sFrecPagos == ("B"))
                    $sFrecPagos = "BIMESTRAL";
                if($sFrecPagos == ("D"))
                    $sFrecPagos = "DIARO";
                if($sFrecPagos == ("H"))
                    $sFrecPagos = "POR HORA";
                if($sFrecPagos == ("K"))
                    $sFrecPagos = "CATORCENAL";
                if($sFrecPagos == ("M"))
                    $sFrecPagos = "MENSUAL";
                if($sFrecPagos == ("P"))
                    $sFrecPagos = "DEDUCCION DEL SALARIO";
                if($sFrecPagos == ("Q"))
                    $sFrecPagos = "TRIMESTRAL";
                if($sFrecPagos == ("S"))
                    $sFrecPagos = "QUINCENAL";
                if($sFrecPagos == ("V"))
                    $sFrecPagos = "VARIABLE";
                if($sFrecPagos == ("W"))
                    $sFrecPagos = "SEMANAL";
                if($sFrecPagos == ("Y"))
                    $sFrecPagos = "ANUAL";
                if($sFrecPagos == ("Z"))
                    $sFrecPagos = "PAGO MINIMO PARA CUENTAS REVOLVENTES";
            }
            if ($sEtiq == ("12")) {
                $sMonto = substr($pCuenta ,$iPos,$iPos+$iLong);	//Monto a Pagar
            }
            if ($sEtiq == ("13")) {
                $sFecAper = substr($pCuenta, $iPos,$iPos+$iLong);	//Fecha apertura
                $fFecAper = $this->convierte_fecha($sFecAper);
            }
            if ($sEtiq == ("14")) {
                $sFecUlt = substr($pCuenta , $iPos, $iPos + $iLong);	//Fecha ultimo pago
                $fFecUlt = $this->convierte_fecha($sFecUlt);
            }
            if ($sEtiq == ("15")) {
                $sFecUltc = substr($pCuenta , $iPos, $iPos + $iLong);	//Fecha ultima compra
                $fFecUltc = $this->convierte_fecha($sFecUltc);
            }
            if ($sEtiq == ("16")) {
                $sFecCierre = substr($pCuenta , $iPos, $iPos + $iLong);	//Fecha cierre
                $fFecCierre = $this->convierte_fecha($sFecCierre);
            }
            if ($sEtiq == ("17")) {
                $sFecRep = substr($pCuenta , $iPos, $iPos + $iLong);	//Fecha cierre
                $fFecRep = $this->convierte_fecha($sFecRep);
            }
            if ($sEtiq == ("21")) {
                $sCremax = substr($pCuenta , $iPos, $iPos+$iLong);	//Saldo Actual
                $car = substr($sCremax , strlen($sCremax)-1 , strlen($sCremax));
				if($car == '+'  || $car == '-')
                {
                    $sCremax = substr($pCuenta , $iPos, $iPos+ $iLong - 1);
                    if($car == '-')
                        $sCremax = "-" . $sCremax;
                }
			}
            if ($sEtiq == ("22")) {
                $sSaldo = substr($pCuenta , $iPos,$iPos+$iLong);	//Saldo Actual
                $car = substr($sSaldo , strlen($sSaldo)-1, strlen($sSaldo));
				if($car == '+'  || $car == '-')
                {
                    $sSaldo = substr($pCuenta , $iPos , $iPos + $iLong - 1);
                    if($car == '-')
                        $sSaldo = "-" . $sSaldo;
                }
			}
            if ($sEtiq == ("23")) {
                $sLimite = substr($pCuenta , $iPos,$iPos+$iLong);//Saldo Vencido
            }
            if ($sEtiq == ("24")) {
                $sVencido = substr($pCuenta , $iPos,$iPos + $iLong);	//Saldo Vencido
            }
            if ($sEtiq == ("25")) {
                $sNumVenc = substr($pCuenta , $iPos,$iPos+$iLong);	//Num Pagos Vencidos
            }
            if ($sEtiq == ("26")) {
                $sMOP = substr($pCuenta , $iPos, $iPos+$iLong);		//Forma Pago
                if($sMOP == ("UR"))
                    $sMOP .= "=CUENTA SIN INFORMACION";
                if($sMOP == ("00"))
                    $sMOP .= "=MUY RECIENTE PARA SER INFORMADA";
                if($sMOP == ("01"))
                    $sMOP .= "=CUENTA AL CORRIENTE";
                if($sMOP == ("02"))
                    $sMOP .= "=ATRASO DE 01 A 29 DIAS";
                if($sMOP == ("03"))
                    $sMOP .= "=ATRASO DE 30 A 59 DIAS";
                if($sMOP == ("04"))
                    $sMOP .= "=ATRASO DE 60 A 89 DIAS";
                if($sMOP == ("05"))
                    $sMOP .= "=ATRASO DE 90 A 119 DIAS";
                if($sMOP == ("06"))
                    $sMOP .= "=ATRASO DE 120 A 149 DIAS";
                if($sMOP == ("07"))
                    $sMOP .= "=ATRASO DE 150 A 12 MESES";
                if($sMOP == ("96"))
                    $sMOP .= "=ATRASO DE 12 MESES";
                if($sMOP == ("97"))
                    $sMOP.= "=CUENTA CON DEUDA PARCIAL O TOTAL SIN RECUPERAR";
                if($sMOP == ("99"))
                    $sMOP .= "=FRAUDE COMETIDO POR EL CLIENTE";
            }
            if($sEtiq == ("27")){
                $sHistorial = substr($pCuenta , $iPos, $iPos + $iLong); //Historial
                
            }
            if ($sEtiq == ("28")) {
                $sFecRec = substr($pCuenta , $iPos , $iPos + $iLong);	//Fecha cierre
                $fFecRec = $this->convierte_fecha($sFecRec);
            }
            if($sEtiq == ("29")){
                $sFecAnt = substr($pCuenta , $iPos,$iPos+$iLong);	//Fecha cierre
                $fFecAnt = $this->convierte_fecha($sFecAnt);
            }
            if ($sEtiq == ("30")) {
                $sObs = subsr($pCuenta , $iPos , $iPos + $iLong);		//Observacion
                if($sObs == ("AD"))
                    $sObs .="=CUENTA EN DISPUTA";
                if($sObs == ("CA"))
                    $sObs .="=CUENTA AL CORRIENTE VENDIDA";
                if($sObs == ("CC"))
                    $sObs .="=CUENTA CERRADA POR EL CONSUMIDOR";
                if($sObs == ("CI"))
                    $sObs .="=CANCELADA POR INACTIVIDAD";
                if($sObs == ("CM"))
                    $sObs .="=CANCELADA POR EL OTORGANTE";
                if($sObs == ("CL"))
                    $sObs .="=CUENTA EN COBRANZA PAGADA TOTALMENTE";
                if($sObs == ("CP"))
                    $sObs.="=CARTERA VENCIDA";
                if($sObs == ("CR"))
                    $sObs .="=DACION EN RENTA";
                if($sObs == ("CV"))
                    $sObs .="=CUENTA VENCIDA VENDIDA";
                if($sObs == ("CZ"))
                    $sObs .="=CANCELADA CON SALDO CERO";
                if($sObs == ("DP"))
                    $sObs .="=PAGOS DIFERIDOS";
                if($sObs == ("DR"))
                    $sObs .="=DISPUTA RESUELTA, CONSUMIDOR INCONFORME";
                if($sObs == ("FD"))
                    $sObs .="=CUENTA FRAUDULENTA";
                if($sObs == ("FN"))
                    $sObs .="=CUENTA FRAUDULENTA NO ATRIBUIBLE AL CONSUMIDOR";
                if($sObs == ("FP"))
                    $sObs .="=CANCELACION DE ADJUDICACION DE INMUEBLE POR PAGO";
                if($sObs == ("FR"))
                    $sObs .="=ADJUDICACION DE INMUEBLE EN PROCESO";
                if($sObs == ("IA"))
                    $sObs .="=CUENTA INACTIVA";
                if($sObs == ("IR"))
                    $sObs .="=ADJUDICACION INVOLUNTARIA";
                if($sObs == ("LC"))
                    $sObs .="=QUITA POR IMPORTE MENOR ACORDADA CON EL CONSUMIDOR";
                if($sObs == ("LG"))
                    $sObs .="=QUITA POR IMPORTE MENOR POR PROGRAMA INSTITUCIONAL";
                if($sObs == ("LS"))
                    $sObs .="=TARJETA DE CREDITO EXTRAVIADA O ROBADA";
                if($sObs == ("MD"))
                    $sObs .="=PAGO PARCIAL EFECTUADO A CUENTA IRRECUPERABLE";
                if($sObs == ("NA"))
                    $sObs .="=CUENTA AL CORRIENTE VENDIDA A UN NO USUARIO DE BC";
                if($sObs == ("NV"))
                    $sObs .="=CUENTA VENCIDA VENDIDA A UN NO USUARIO DE BC";
                if($sObs == ("PC"))
                    $sObs.="=ENVIADO A DESPACHO DE COBRANZA";
                if($sObs == ("PD"))
                    $sObs .="=ADJUDICACION CANCELADA POR PAGO";
                if($sObs == ("PL"))
                    $sObs .="=LIMITE EXCEDIDO";
                if($sObs == ("PS"))
                    $sObs .="=SUSPENSION DE PAGO";
                if($sObs == ("RA"))
                    $sObs .="=CUENTA AL CORRIENTE RESTRUCTURADA POR PROGRAMA INSTITUCIONAL";
                if($sObs == ("RC"))
                    $sObs .="=CUENTA AL CORRIENTE RESTRUCTURADA ACORDADA CON EL CONSUMIDOR";
                if($sObs == ("RE"))
                    $sObs .="=CUENTA AL CORRIENTE RESTRUCTURADA PAGADA TOTALMENTE";
                if($sObs == ("RF"))
                    $sObs .="=REFINANCIADA";
                if($sObs == ("RO"))
                    $sObs .="=CUENTA VENCIDAD RESTRUCTURADA POR PROGRAMA INSTITUCIONAL";
                if($sObs == ("RR"))
                    $sObs .="=RESTITUCION DEL BIEN";
                if($sObs == ("RV"))
                    $sObs .="=CUENTA VENCIDA RESTRUCTURADA ACORDADA CON EL CONSUMIDOR";
                if($sObs == ("SC"))
                    $sObs .="=DEMANDA RESUELTA EN FAVOR DEL CONSUMIDOR";
                if($sObs == ("SG"))
                    $sObs .="=DEMANDA POR EL OTORGANTE";
                if($sObs == ("SP"))
                    $sObs .="=DEMANDA RESUELTA A FAVOR DEL OTORGANTE";
                if($sObs == ("ST"))
                    $sObs .="=ACUERDO POR IMPORTE MENOR";
                if($sObs == ("SU"))
                    $sObs .="=DEMANDA POR EL CONSUMIDOR";
                if($sObs == ("TC"))
                    $sObs .="=SUSTICION DE DEUDOR";
                if($sObs == ("TL"))
                    $sObs .="=TRANSFERENCIA A NUEVO OTORGANTE";
                if($sObs == ("TR"))
                    $sObs .="=TRANSFERIDA A OTRA AREA";
                if($sObs == ("UP"))
                    $sObs .="=CUENTA QUE CAUSA QUEBRANTO";
                if($sObs == ("VR"))
                    $sObs .="=DACION EN PAGO";
            }
            if ($sEtiq == ("37")) {
                $sFecPeor = substr($pCuenta , $iPos, $iPos + $iLong);		//Fecha peor atraso
                $fFecPeor = $this->convierte_fecha($sFecPeor);
            }
            if ($sEtiq == ("38")) {
                $sMOP2 = substr($pCuenta , $iPos, $iPos + $iLong);		//Forma Pago
                if($sMOP2 == ("UR"))
                    $sMOP2 .= "=CUENTA SIN INFORMACION";
                if($sMOP2 == ("00"))
                    $sMOP2 .= "=MUY RECIENTE PARA SER INFORMADA";
                if($sMOP2 == ("01"))
                    $sMOP2 .= "=CUENTA AL CORRIENTE";
                if($sMOP2 == ("02"))
                    $sMOP2 .= "=ATRASO DE 01 A 29 DIAS";
                if($sMOP2 == ("03"))
                    $sMOP2 .= "=ATRASO DE 30 A 59 DIAS";
                if($sMOP2 == ("04"))
                    $sMOP2 .= "=ATRASO DE 60 A 89 DIAS";
                if($sMOP2 == ("05"))
                    $sMOP2 .= "=ATRASO DE 90 A 119 DIAS";
                if($sMOP2 == ("06"))
                    $sMOP2 .= "=ATRASO DE 120 A 149 DIAS";
                if($sMOP2 == ("07"))
                    $sMOP2 .= "=ATRASO DE 150 A 12 MESES";
                if($sMOP2 == ("96"))
                    $sMOP2 .= "=ATRASO DE 12 MESES";
                if($sMOP2 == ("97"))
                    $sMOP2 .= "=CUENTA CON DEUDA PARCIAL O TOTAL SIN RECUPERAR";
                if($sMOP2 == ("99"))
                    $sMOP2 .= "=FRAUDE COMETIDO POR EL CLIENTE";

            }
            if (($sEtiq == ("TL")) || ($sEtiq == ("IQ"))  || ($sEtiq == ("RS")))  {
                
                $iLong = $iPos - 4;
                $iPos = $xLong;
            }
            $iPos .= $iLong;
        }
		if($sLimite == null || $sLimite  == ""){
            $sLimite = "0";
        }

		
		if ($xLong < $iPos) {
            $sInsert = "Insert into Circulo_cuentas(folioconsultaotorgante, fechaactualizacion, registroimpugnado, claveotorgante, nombreotorgante, " .
                "tiporesponsabilidad, tipocuenta, numeropagos, frecuenciapagos, fechaaperturacuenta, fechaultimopago, fechacierrecuenta, " .
                "saldoactual, saldovencido, numeropagosvencidos, historicopagos,claveunidadmonetaria, pagoactual, fechapeoratraso," .
                "saldovencidopeoratraso,limitecredito, montopagar, tipocredito, peoratraso, observacion, creditomaximo, fechaultimacompra, " .
                "fecharecientehistoricopagos, fechaantiguahistoricopagos, tipo_credito_id, fechainformacion) values(" .
                $fol . ",'" . $fFecAct . "','" . $sImp . "','" . $clOtor . "','" . $nomOtor ."','" . $sResp . "','" . $sTipo . "'," . $sNumPagos . ",'" . $sFrecPagos . "','" . $fFecAper . "','" . $fFecUlt. "','" . $fFecCierre . "'," . $sSaldo . "," . $sVencido . "," . $sNumVenc . ",'" . $sHistorial . "','" . $sMoneda . "','"
                . $sMOP . "','" . $fFecPeor . "','" . $sSaldov . "'," . $sLimite . "," . $sMonto . ",'" . $sContrato . "','" . $sMOP2 .
                "','" . $sObs ."'," . $sCremax .",'" . $fFecUltc ."','" . $fFecRec . "','" . $fFecAnt . "','" . $sTContrato . "','" . $fFecRep . "')";
        } else {
            $sInsert = "Update consultas_circulo set error = 'incompleto " . substr($pCuenta , 0, $xLong) .  "' , fecha_creacion = CURRENT_TIMESTAMP where folioconsulta = " . $fol;
            $iLong = -1;
        }
		try{
            $st = DB::insert($sInsert);            
        } catch(\Exception $e) {
            Log::info($e);
        	$iLong = -1;
		}
		return $iLong;
	}


	public  function lee_consultas($pConsultas, $fol) {
        $sFecCons = "";
		$sBuro = "";
		$clOtor = "";
		$nomOtor = "";
		$telOtor = "";
		$sContrato = "";
		$sMonto = "";
		$sMoneda = "";
		$sResp = "";
		$sConsumidor = "";
		$sEtiq = "";
		$sLong = "";
		$fFecCons = new DateTime(0,9,9);
		$iPos = 0;
		$iLong = 0;
		$xLong = strlen($pConsultas);
		// System.out.println(pConsultas + " " + xLong + " ipos " + iPos);
		while ($iPos < $xLong) {
            $sEtiq = substr($pConsultas , $iPos, $iPos + 2);
            // System.out.println("etiqueta : " + $sEtiq);
            $iPos = $iPos + 4;
            $sLong = substr($pConsultas , $iPos-2, $iPos);
            $iLong = (int)$sLong;
            if ($sEtiq == ("IQ")) {
                if ($iPos == 4) {
                    $sFecCons = substr($pConsultas , $iPos,$iPos + $iLong);  //Fecha de Actualizacion
                    $fFecCons = $this->convierte_fecha($sFecCons);
                    $sEtiq = "";
                }
            }
            if ($sEtiq == ("00")) {
                $sBuro = substr($pConsultas , $iPos, $iPos + $iLong);  	//Impugnado por el cliente
            }
            if ($sEtiq == ("01")) {
                $clOtor = substr($pConsultas , $iPos,$iPos + $iLong);  	//Clave Otorgante
            }
            if ($sEtiq == ("02")) {
                $nomOtor = substr($pConsultas , $iPos, $iPos + $iLong);  //Nombre Otorgante
            }
            if ($sEtiq == ("03")) {
                $telOtor = substr($pConsultas , $iPos, $iPos + $iLong);  //Telefono Otorgante
            }
            if ($sEtiq == ("04")) {
                $sContrato = substr($pConsultas , $iPos,$iPos + $iLong);	//Tipo de contrato
                if($sContrato == ("AF"))
                    $sContrato="APARATOS/MUEBLES";
                if($sContrato == ("AG"))
                    $sContrato="AGROPECUARIO (PFAE)";
                if($sContrato == ("AL"))
                    $sContrato="ARRENDAMIENTO AUTOMOTRIZ";
                if($sContrato == ("AP"))
                    $sContrato="AVIACION";
                if($sContrato == ("AU"))
                    $sContrato="COMPRA DE AUTOMOVIL";
                if($sContrato == ("BD"))
                    $sContrato="FIANZA";
                if($sContrato == ("BT"))
                    $sContrato="BOTE/LANCHA";
                if($sContrato == ("CC"))
                    $sContrato="TARJETA DE CREDITO";
                if($sContrato == ("CE"))
                    $sContrato="CARTAS DE CREDITO (PFAE)";
                if($sContrato == ("CF"))
                    $sContrato="CREDITO FISCAL";
                if($sContrato == ("CL"))
                    $sContrato="LINEA DE CREDITO";
                if($sContrato == ("CO"))
                    $sContrato="CONSOLIDACION";
                if($sContrato == ("CS"))
                    $sContrato="CREDITO SIMPLE (PFAE)";
                if($sContrato == ("CT"))
                    $sContrato="CON COLATERAL";
                if($sContrato == ("DE"))
                    $sContrato="DESCUENTOS (PFAE)";
                if($sContrato == ("EQ"))
                    $sContrato="EQUIPO";
                if($sContrato == ("FI"))
                    $sContrato="FIDEICOMISOS (PFAE)";
                if($sContrato == ("FT"))
                    $sContrato="FACTORAJE";
                if($sContrato == ("HA"))
                    $sContrato="HABILITACION O AVIO (PFAE)";
                if($sContrato == ("HE"))
                    $sContrato="PRESTAMO TIPO 'HOME EQUITY'";
                if($sContrato == ("HI"))
                    $sContrato="MEJORAS A LA CASA";
                if($sContrato == ("LS"))
                    $sContrato="ARRENDAMIENTO";
                if($sContrato == ("MI"))
                    $sContrato="OTROS";
                if($sContrato == ("OA"))
                    $sContrato="OTROS ADEUDOS VENCIDOS (PFAE)";
                if($sContrato == ("PA"))
                    $sContrato="PRESTAMOS PARA PERSONAS FISICAS CON ACTIVIDAD EMPRESARIAL PFAE)";
                if($sContrato == ("PB"))
                    $sContrato="EDITORIAL";
                if($sContrato == ("PG"))
                    $sContrato="PGUE (PRESTAMO CON GARANTIAS DE UNIDADES INDUSTRIALES)(PFAE)";
                if($sContrato == ("PL"))
                    $sContrato="PRESTAMO PERSONAL";
                if($sContrato == ("PR"))
                    $sContrato="PRENDARIO (PFAE)";
                if($sContrato == ("PQ"))
                    $sContrato="QUIROGRAFARIO (PFAE)";
                if($sContrato == ("RC"))
                    $sContrato="REESTRUCTURADO";
                if($sContrato == ("RD"))
                    $sContrato="REDESCUENTO (PFAE)";
                if($sContrato == ("RE"))
                    $sContrato="BIENES RAICES";
                if($sContrato == ("RF"))
                    $sContrato="REFACCIONARIO (PFAE)";
                if($sContrato == ("RN"))
                    $sContrato="RENOVADO (PFAE)";
                if($sContrato == ("RV"))
                    $sContrato="VEHICULO RECREATIVO";
                if($sContrato == ("SC"))
                    $sContrato="TARJETA GARANTIZADA";
                if($sContrato == ("SE"))
                    $sContrato="PRESTAMO GARANTIZADO";
                if($sContrato == ("SG"))
                    $sContrato="SEGUROS";
                if($sContrato == ("SM"))
                    $sContrato="SEGUNDA HIPOTECA";
                if($sContrato == ("ST"))
                    $sContrato="PRESTAMO PARA ESTUDIANTE";
                if($sContrato == ("TE"))
                    $sContrato="TARJETA DE CREDITO EMPRESARIAL";
                if($sContrato == ("UK"))
                    $sContrato="DESCONOCIDO";
                if($sContrato == ("US"))
                    $sContrato="PRESTAMO NO GARANTIZADO";
            }
            if ($sEtiq == ("05")) {
                $sMoneda = substr($pConsultas , $iPos, $iPos + $iLong);	//Moneda
                if($sMoneda != "MX" || $sMoneda != "US" || $sMoneda != "UD")
                    $sMoneda ="MX";
            }
            if ($sEtiq == ("06")) {
                $sMonto = substr($pConsultas , $iPos, $iPos + $iLong);	//Monto del contrato
            }
            if ($sEtiq == ("07")) {
                $sResp = substr($pConsultas , $iPos, $iPos + $iLong);			//Tipo de Responsabilidad
                if($sResp == ("I"))
                    $sResp = "INDIVIDUAL";
                if($sResp == ("J"))
                    $sResp = "MANCOMUNADO";
                if($sResp == ("C"))
                    $sResp = "OBLIGADO SOLIDARIO";
            }
            if ($sEtiq == ("08")) {
                $sConsumidor = substr($pConsultas , $iPos, $iPos + $iLong);	//Nuevo  consumidor
            }
            if (($sEtiq == ("IQ"))  || ($sEtiq == ("RS")))  {
                $iLong = $iPos - 4;
                $iPos = $xLong;
            }

            $iPos .= $iLong;
        }
		$sInsert = "Insert into circulo_consultas_efectuadas(folioconsultaotorgante, fechaconsulta, claveotorgante, nombreotorgante, tipocredito, importecredito, tiporesponsabilidad, claveunidadmonetaria) values(" .
        $fol . ",'" .  $fFecCons . "','" . $clOtor . "','" . $nomOtor . "','" . $sContrato . "'," . $sMonto  . ",'" . $sResp ."','" . $sMoneda . "')";
		try{

            $st = DB::insert($sInsert);            

        }
        catch(\Exception $e){
            Log::info($e);
			$iLong = -1;
		}


		return $iLong;
	}

	public function lee_fin($cad)
	{
        return substr($cad, strlen($cad) - 15, strlen($cad)-6);
    }

	private  function convierte_fecha ($xFecha){
        $fFecha = new Datetime('1988-8-8');
		return $fFecha = substr($xFecha ,4,8) . "-" . substr($xFecha , 2, 4) . "-" . substr($xFecha , 0,2);
	}

}
