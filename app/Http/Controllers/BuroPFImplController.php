<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;

use DB;
use Response;

class BuroPFImplController extends Controller
{
   /*
    * TEST
    */
    public function consulta($conn, $solicitante, $noEtapa, $valorEtapa, $ProductoRequerido, $TipoCuenta, $ClaveUnidadMonetaria, $ImporteContrato, $usuario_id_sol, $usuario_id_aut, $computadora, $confirma, $sucursal) {
        return '';
    }

    public  function testObject(){
        $test = array();
        //echo "This is test object";
        $test['name'] = 'tttttttt';
        return Response::json($test);
    }

//    function socketTest() {
//        $fp = fsockopen($host, $port, $errno, $errstr);
//        if (!$fp) {
//            echo "$errstr ($errno)<br>\n";
//        } else {
//            $out = "$method $path HTTP/1.1\r\n";
//            $out .= "Host: $host\r\n";
//            $out .= "User-Agent: " . $_SERVER['HTTP_USER_AGENT'] . "\r\n";
//            $out .= "Content-type: application/x-www-form-urlencoded\r\n";
//            $out .= "Content-length: " . strlen($data) . "\r\n";
//            $out .= "Connection: close\r\n\r\n";
//            $out .= "$data\r\n\r\n";
//            fwrite($fp, $out);
//            while (!feof($fp)) {
//                $line = fgets($fp, 1024);
//                $buffer .= $line;
//            }
//            fclose($fp);
//        }
//    }
    //////////////////////////////////////////////////////////////
    
    public $iFolioid = 0 ;
    /*
     * consultac
     */
    public function consulta($conn, $Solicitante, $noEtapa , $valorEtapa, $ProductoRequerido, $TipoCuenta,
                    $ClaveUnidadMonetaria, $ImporteContrato, $usuario_id_sol, $usuario_id_aut,
                    $computadora, $confirma, $sucursal) {
       $int folio = -1;
       $xConsulta = "";
       $out = null; //DataOutputStream out = null;
       $in = null; //DataInputStream in = null;
        
       //Logger.getAnonymousLogger().info(Solicitante);
       $buroSocket; //Socket buroSocket;
       $xrespuestaBuro  = "";

       try {
           try{

               $sqldatos = "select usuario_buro,contrasena_buro,servidor_buro,puerto_buro from cat_empresas where clave = " + sucursal;
               $res = DB::select($sqldatos);
               
               $usuario = $res->usuario_buro;
               $contrasena = $res ->contrasena_buro;
               $host = $res ->servidor_buro;
               $puerto = $res ->puerto_buro;
               $puerto_buro_credito = (int)$puerto;

               $xConsulta = $this->cadenaconsulta($Solicitante, (int)$ImporteContrato, $ProductoRequerido, $usuario, $contrasena);
               //Quitamos la letra � de la cadena de consulta para que no marque error en buro.
               $xConsulta = str_replace("�", "N" , $xConsulta); 

               $fp = fsockopen($host, $puerto_buro_credito, $errno, $errstr);
                if ($fp) {
                    $out = "$method $path HTTP/1.1\r\n";
                    $out .= "Host: $host\r\n";
                    $out .= "User-Agent: " . $_SERVER['HTTP_USER_AGENT'] . "\r\n";
                    $out .= "Content-type: application/x-www-form-urlencoded\r\n";
                    $out .= "Content-length: " . strlen($xConsulta) . "\r\n";
                    $out .= "Connection: close\r\n\r\n";
                    $out .= "\u0013"; // add
                    $out .= "\n"; //add
                    $out .= "\n"; //add
                    $out .= "$xConsulta\r\n\r\n";
                    
                    fwrite($fp, $out);
                    while (!feof($fp)) {
                        $line = fgets($fp, 1024);
                        $buffer .= $line;
                    }

                    fclose($fp);
                }
             
               // Logger.getAnonymousLogger().info("lectura:" + xConsulta );
               // System.out.println(xConsulta);
               
               //Logger.getAnonymousLogger().info(xConsulta);
               
               //$in = new DataInputStream( buroSocket.getInputStream() );
                   //$linea = "";

               $car = 0;
               StringBuffer sb = new StringBuffer();
               try {
                   Logger.getAnonymousLogger().info("empieza lectura");
                   while ( (car = in.read() ) >= 0 )  {
                   if ( car == '\u0013')
                   break;

                   sb.append((char)car);
               }
               } catch( \Exception $e ) {
                   // Logger.getAnonymousLogger().info(e.toString());
                   // for( int i=0; i!= e.getStackTrace().length; i++) {
                   //     Logger.getAnonymousLogger().info(e.getStackTrace()[i].toString());
                   // }

               }
               $xrespuestaBuro = $sb;
               
               //Logger.getAnonymousLogger().info("regreso "  +xrespuestaBuro);
               
               $folio = $this->inserta_tabla_consultacirculo($xConsulta, $xrespuestaBuro, $Solicitante, $noEtapa , $valorEtapa, 
                            $ProductoRequerido, $TipoCuenta, $ClaveUnidadMonetaria, $ImporteContrato, $usuario_id_sol, $usuario_id_aut, 
                            $computadora, $confirma, $sucursal);
           } catch(\Exception $se) {
                   $folio = -1;
                   // Logger.getAnonymousLogger().info(se.toString());
                   // for( int i=0; i!= se.getStackTrace().length; i++) {
                   //     Logger.getAnonymousLogger().info(se.getStackTrace()[i].toString());
                   // }
                   $folio = $this->inserta_tabla_consultacirculo($se , $xrespuestaBuro, $Solicitante, $noEtapa , $valorEtapa, 
                                $ProductoRequerido, $TipoCuenta, $ClaveUnidadMonetaria, $ImporteContrato, $usuario_id_sol, $usuario_id_aut, 
                                $computadora, $confirma, $sucursal);
           }                           
       } catch( \Exception $se) {
               $folio = -1;
               // Logger.getAnonymousLogger().info(se.toString());
               // for( int i=0; i!= se.getStackTrace().length; i++) {
               //     Logger.getAnonymousLogger().info(se.getStackTrace()[i].toString());
               // }
                $folio = $this->inserta_tabla_consultacirculo($se , $xrespuestaBuro, $Solicitante, $noEtapa , $valorEtapa, 
                                $ProductoRequerido, $TipoCuenta, $ClaveUnidadMonetaria, $ImporteContrato, $usuario_id_sol, 
                                $usuario_id_aut, $computadora, $confirma, $sucursal);
       }  
        return $folio;
    }

    /*
     * respuesta
     */
	public  function respuesta($conn, $sfolio) {
    // TODO Auto-generated method stub
        $idr = 0;
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
		$sValorn = "select respuestaxml from consultas_circulo where folioconsulta = '" + sfolio + "'";
		try{

            $sInsert = DB::delete("Delete from circulo_personas where folioconsultaotorgante = " + $sfolio);
            $sInsert = DB::delete("Delete from circulo_domicilios where folioconsultaotorgante = " + $sfolio);
            $sInsert = DB::delete("Delete from circulo_cuentas where folioconsultaotorgante = " + $sfolio);
            $sInsert = DB::delete("Delete from circulo_empleos where folioconsultaotorgante = " + $sfolio);
            $sInsert = DB::delete("Delete from circulo_consultas_efectuadas  where folioconsultaotorgante = " + sfolio);

            $res2 = DB::select($sValorn);
			$sTexto = reset($res2); //get first item

			//Logger.getAnonymousLogger().info(sTexto);
			$par = (int) $sfolio;
			//Logger.getAnonymousLogger().info(String.valueOf(par));
			if($sTexto != "")
            {
                $b1 = 0;
				$longitud = $sTexto.length(); // confrim $or array
				//Logger.getAnonymousLogger().info(String.valueOf(longitud));
				if ($longitud > 0) {
                    $sINTL = substr($sTexto, 47, 48);
                    if ($longitud < 382) {
                        $sNombre = substr($sTexto, 49,$longitud);	//NOMBRE
                    }
                    else {
                        $sNombre = substr($sTexto, 49,384);	//NOMBRE
                    }
                    //Logger.getAnonymousLogger().info("1");
                    $b1 = $this->lee_nombre($sNombre, $par);
                    if($b1 < 0)
                    {
                        $Error = "NOMBRE";
                        $longitud = -1;
                    }
                    //System.out.println("B1: "+String.valueOf(b1)+" Long: "+String.valueOf(longitud));
                    $b1 += 49;
                    if ($b1 < $longitud) {
                        do {
                            if ($longitud < ($b1+313)) {
                                $sDireccion = substr($sTexto, $b1, $longitud);
                            }
                            else {
                                //System.out.println("Longitud texto: " + String.valueOf(sTexto.length()));
                                $sDireccion = substr($sTexto, $b1, $b1+315);
                                //System.out.println("La cadena direcci�n es: " + sDireccion);
                            }
                            $b1 += $this->lee_dir($sDireccion, $par);
                            $xEtiq = substr($sTexto, $b1, $b1+2);
                        } while ( $xEtiq  =="PA" && $b1 != -1);
                        if($b1 < 0)
                        {
                            $Error += ", DIRECCION";
                            $idr = $longitud = -1;
                        }
                    }
                    if ($b1 < $longitud) {
                        //System.out.println("Empleo B1: " + String.valueOf(b1)+ " Long: " + String.valueOf(longitud));
                        do {
                            if ($longitud < ($b1+453)) {
                                $sEmpleo = substr($sTexto, $b1,$longitud);
                            }
                            else {
                                if(($b1+545) <= $longitud )
                                {
                                    $sEmpleo = substr($sTexto, $b1, $b1+545);
                                }
                                else
                                {
                                    $sEmpleo = substr($sTexto , $b1, $longitud);
                                }
                                //System.out.println("La cadena de empleo es: " + sEmpleo);
                            }
                            $b1 += $this->lee_empleo($sEmpleo, $par);
                            $xEtiq = substr($sTexto, $b1, $b1+2);
                        } while ($xEtiq == "PE" && $b1 != -1);

                        if($b1 < 0)
                        {
                            $Error = ", EMPLEO";
                            $idr = $longitud += -1;
                        }
                        //System.out.println("Salio del segmento de empleo, con B1 en: " + b1);
                    }
                    if ($b1 < $longitud) {
                        //System.out.println("Entra al segmento de cuentas, con B1 en: " + b1 +" y Longitud en: " + longitud);
                        do {
                            if ($longitud < ($b1+478)) {
                                $sCuentas = substr($sTexto, $b1,$longitud);
                                //System.out.println("La cadena de cuentas es: " + sCuentas);
                            }
                            else {

                                //sCuentas = sTexto.substring(b1,b1+480);
                                if 	($longitud < ($b1+480))
                                    $sCuentas = substr($sTexto, $b1,$longitud);
                                else
                                    $sCuentas = substr($sTexto, $b1, $b1+480);
                                //System.out.println("La cadena de cuentas es: " + sCuentas);
                            }
                            $b1 += $this->lee_cuentas($sCuentas, $par);
                            $xEtiq = substr($sTexto, $b1, $b1+2);

                        } while ( $xEtiq == "TL" && $b1 != -1);
                        if($b1 < 0)
                        {
                            $Error += ", CUENTAS";
                            $idr = $longitud = -1;
                        }

                    }
                    if ($b1 < $longitud) {
                        do {
                            if ($longitud < ($b1+133)) {
                                $sCuentas = substr($sTexto, $b1,$longitud);
                                //System.out.println("La cadena de consultas es: " + sCuentas);
                            }
                            else {
                                $sCuentas = substr($sTexto , $b1, $b1+135);
                                //System.out.println("La cadena de consultas es: " + sCuentas);
                            }
                            $b1 += $this->lee_consultas($sCuentas, $par);
                            $xEtiq = substr($sTexto, $b1, $b1+2);
                        } while ($xEtiq == "IQ" && $b1 != -1);
                        if($b1 < 0)
                        {
                            $Error = ", CONSULTAS";
                            $idr = $longitud = -1;
                        }
                    }
                }
				if($idr==-1)
                {
                    $Err = "ERROR DE INTERPRETACION DE SEGMENTO(S): " + Error;
				}
			}
		} catch(\Exception $e) {
           // Logger.getAnonymousLogger().info(e.toString());
           // for( int i=0; i!= e.getStackTrace().length; i++) {
           //     Logger.getAnonymousLogger().info(e.getStackTrace()[i].toString());
           // }
                $idr = -1;
                $Err += e.toString();
		}
		if($idr == -1)
        {
            $sInsert = DB::update("Update consultas_circulo set error = '" + $Err + "' , fecha_creacion = CURRENT_TIMESTAMP where folioconsulta = " + $sfolio);

		    try{
                //Logger.getAnonymousLogger().info(sInsert);
            } catch(\Exception $e) {
               // Logger.getAnonymousLogger().info(e.toString());
               // for( int i=0; i!= e.getStackTrace().length; i++) {
               //     Logger.getAnonymousLogger().info(e.getStackTrace()[i].toString());
               // }
            }
		}
        else
        {
            //Logger.getAnonymousLogger().info(sValorn);
            $numc = $this->lee_fin($sTexto);
			$sInsert2 = "Update consultas_circulo set control = '" + $numc + "' , cuenta_buro = "+ $numc +" , fecha_creacion = CURRENT_TIMESTAMP where folioconsulta = " + $sfolio ;
            $sInsert2 = DB::update($sInsert2);
			try{
                //System.out.println("control , " + sInsert2);
                //Logger.getAnonymousLogger().info(sInsert2);
            }
            catch(\Exception $e){
               // Logger.getAnonymousLogger().info(e.toString());
               // for( int i=0; i!= e.getStackTrace().length; i++) {
               //     Logger.getAnonymousLogger().info(e.getStackTrace()[i].toString());
               // }
			}

			$sInsert2 = "select buro_califica_cuentas(" + $sfolio +")";
			try{
                $sInsert2 = DB::select($sInsert2);
                //System.out.println("control , " + sInsert2);
                //gger.getAnonymousLogger().info(sInsert2);
            }
            catch(\Exception $e){
                //Logger.getAnonymousLogger().info(e.toString());
                // for( int i=0; i!= e.getStackTrace().length; i++) {
                //     Logger.getAnonymousLogger().info(e.getStackTrace()[i].toString());
                // }

			}
		}

		return $idr     ;
	}

    /*
     * inserta_tabla_consultacirculo
     */
	private  function inserta_tabla_consultacirculo($con, $res, $idsol, $noEtapa , $valorEtapa, $ProductoRequerido, $TipoCuenta, $ClaveUnidadMonetaria,$ImporteContrato, $usuario_id_sol, $usuario_id_aut, $computadora, $confirma, $sucursal)
	{

        try {

            $sValorn = "select nextval('Consultas_circulo_s')";
			try {

                $res2 = DB::select($sValorn);                
				$iFolioid = reset($res2); // get first item 

			}catch(\Exception $e) {
                //Logger.getAnonymousLogger().info(e.toString());
                // for( int i=0; i!= e.getStackTrace().length; i++) {
                //     Logger.getAnonymousLogger().info(e.getStackTrace()[i].toString());
                // }
				$iFolioid = -1;
			}
			
			if($iFolioid != -1)
            {
                java.util.regex.Pattern p = java.util.regex.Pattern.compile("'");
				java.util.regex.Matcher m = p.matcher(res);
				StringBuffer sb = new StringBuffer();
				while (m.find()) {
                    m.appendReplacement(sb, " ");
                }
				m.appendTail(sb);
				res= sb.toString();

				$sInsert2 = "Insert into consultas_circulo(folioconsulta, buro, respuestaxml, consultaxml, solicitante, noetapa, valoretapa, tipocuenta, claveunidadmonetaria, importecontrato, usuario_id_sol, usuario_id_aut, computadora, confirma, sucursal, productorequerido, fecha_creacion) values("
                + $iFolioid + ",'B','" + $res +"','" + $con + "','" + $idsol + "'," + $noEtapa + ",'" + $valorEtapa + "','" + $TipoCuenta + "','" + $ClaveUnidadMonetaria + "',"+ $ImporteContrato + ","+ $usuario_id_sol + "," + $usuario_id_aut + ",'" + $computadora + "'," + $confirma + ",'" + $sucursal + "'," + $ProductoRequerido + ", CURRENT_TIMESTAMP)";
				//System.out.println(sInsert2);
                $sInsert2 = DB::insert($sInsert2);				

				//Logger.getAnonymousLogger().info(sInsert2);

			}
		}
        catch(\Exception $e){
            // Logger.getAnonymousLogger().info(e.toString());
            // for( int i=0; i!= e.getStackTrace().length; i++) {
            //     Logger.getAnonymousLogger().info(e.getStackTrace()[i].toString());
            // }
		}

		return $iFolioid;
	}

    /*
    *cadenaconsulta
    */
	private  function cadenaconsulta($nSolicitante, $nMonto, $ProductoRequerido, $usuario, $contrasena) {

        $sINTL="";
		$sNombre = "";
		$sDom = "";
		$sConsulta ="";		
		$sINTL = $this->encabezado($usuario, $contrasena, $nMonto, $ProductoRequerido);
		if (!$sINTL =="") {
            $sNombre = $this->nombre($nSolicitante);
            if ($sNombre != "NOMBRE") {
                $sDom = $this->domicilio($nSolicitante);
                if ($sDom != "DOMIC") {                    
                    $sConsulta = $sINTL + $sNombre + $sDom;
                }
            }
        }
	    $lgt = 15;
	    $lgt += strlen($sConsulta;
	    $Es = "";
	    if($lgt >= 0 && $lgt < 10)
        {
            $Es = "ES050000" + $lgt + "0002**";
        }
	    if($lgt >=10 && $lgt < 100)
        {
            $Es = "ES05000" + $lgt + "0002**";
        }
	    if($lgt >= 100 && $lgt < 1000)
        {
            $Es = "ES0500" + $lgt + "0002**";
        }
	    if($lgt >= 1000 && $lgt < 10000)
        {
            $Es = "ES050" + $lgt + "0002**";
        }
	    if($lgt >=10000 && $lgt < 100000)
        {
            $Es = "ES05" + $lgt + "0002**";
        }

		return $sConsulta + $Es;
	}

    /*
    *encabezado
    */
	private  function encabezado($clave, $pass, $mont, $ProductoRequerido) {
      $b1 = true;

		$sEnc="";

			$sSql = "select coalesce(lim_sup,0) as lim_sup from cat_creditos where clave=" + ProductoRequerido;
			$monto = 0;
			$monto2 = $mont;
			$sMonto = "000000000";
			try {
                $res = DB::select($sSql);
                $monto = res->lim_sup;
                
				if ($monto>99) {
                    $sEnc = "INTL";		//Etiqueta
                    $sEnc = $sEnc + "11"; 	//Version
                    $sEnc = $sEnc + "                         "; 	//Referencia 25 espacios
                    $sEnc = $sEnc + "001"; 	//Producto
                    $sEnc = $sEnc + "MX"; 	//Pais
                    $sEnc = $sEnc + "0000";	//Identificador
                    $sEnc = $sEnc + $clave; 	//Clave
                    $sEnc = $sEnc + $pass; 	//Password
                    $sEnc = $sEnc + "I"; 	//Indicador
                    $sEnc = $sEnc + "CL"; 	//Contrato valo original LC cambio a CL
                    $sEnc = $sEnc + "MX"; 	//Pesos
                    
                    $mto = $monto2;

					$sMonto = substr($sMonto , 0 , strlen($sMonto) - strlen($mto));
					$sMonto = $sMonto + $mto;

					$sEnc = $sEnc + $sMonto; 	//Monto
					$sEnc = $sEnc + "SP"; 	//Idioma
					$sEnc = $sEnc + "01"; 	//Tipo Salida
					$sEnc = $sEnc + "S    "; 	//Tamañ				sEnc=sEnc + "    "; 	//Impresora
					$sEnc = $sEnc + "0000000"; 	//Pesos
				}
			} catch(Exception e) {
                // Logger.getAnonymousLogger().info(e.toString());
                // for( int i=0; i!= e.getStackTrace().length; i++) {
                //     Logger.getAnonymousLogger().info(e.getStackTrace()[i].toString());
                // }
			}
    			//System.out.println(sEnc);

		return $sEnc;
	}


    /*
    *nombre
    */
	private  function nombre($idSolicitante) {

        $sSol = "NOMBRE";		
		$sSql = "select current_Date as vescribe";

		try {

            $res = DB::select($sSql);			
			$fFecAct = $res->vescribe;

		} catch(\Exception $e) {
		     // Logger.getAnonymousLogger().info(e.toString());
             // for( int i=0; i!= e.getStackTrace().length; i++) {
             //    Logger.getAnonymousLogger().info(e.getStackTrace()[i].toString());
             //   }
         }

		$sSql = "select buro_nombre('A','" + $idSolicitante + "',1,1,'', $current_date) as vescribe";
		//Logger.getAnonymousLogger().info("Cadena de nombre:" + sSql );
		
        try {

            $res = DB::select($sSql);            
            foreach ($res as $res1) {
			     $sSol = $res1->vescribe;
            }
            
		} catch(\Exception $e) {
            // Logger.getAnonymousLogger().info(e.toString());
            // for( int i=0; i!= e.getStackTrace().length; i++) {
            //     Logger.getAnonymousLogger().info(e.getStackTrace()[i].toString());
            // }
		}
		return $sSol;
	}

    /*
    *domicilio
    */
	private  function domicilio($idSolicitante) {
        $sSol="DOMIC";
		$sSql = "select buro_domicilio('A','" + idSolicitante + "',1,1) as vescribe";

		try {

            $res = DB::select($sSql);                   
            foreach ($res as $res1) {
                $sSol = $res1->vescribe;
            }        
			
			// Logger.getAnonymousLogger().info(sSol);
		} catch(\Exception $e) {
            // Logger.getAnonymousLogger().info(e.toString());
            // for( int i=0; i!= e.getStackTrace().length; i++) {
            //     Logger.getAnonymousLogger().info(e.getStackTrace()[i].toString());
            // }
		}

		return sSol;
	}

    /*
    *fin
    */
	private  function $fin($sResultado) {
        
        $sFin = "";
		$longitud = 0;
		$longitud = strlen($sResultado);
		if ($longitud > 0) {
            $longitud = $longitud + 15;
            $sFin = "00000" + $longitud;
            $longitud = strlen($sFin);
            $sFin = substr($sFin , $longitud-5 , $longitud);
            $sFin = "ES05" + $sFin + "0002**";
        }

		return $sFin;
	}

    /*
    *lee_nombre
    */
	private  function lee_nombre($pNombre, $fol) {
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
		//$fFecNac = new Date(0,9,9); //= new Date();
        $fFecNac = new DateTime();
		$xLong =$pNombre.length();
		while ($iPos < $xLong) {
            $sEtiq = substr($pNombre , $iPos , $iPos+2);
            $iPos = $iPos + 4;
            $sLong = substr($pNombre , $iPos-2 , $iPos);
            $iLong = $sLong; //Long.parseInt(sLong);
            if ($sEtiq == "PN" ) {
                $sPaterno = substr($pNombre , $iPos , $iPos + $iLong);  //Apellido Paterno
            }
            if ($sEtiq  == "00" ) {
                $sMaterno = substr($pNombre , $iPos , $iPos + $iLong);  //Apellido Materno
            }
            if ($sEtiq == "01") {
                $sAdicional = substr($pNombre , $iPos , $iPos + $iLong);  //Apellido Adicional
            }
            if ($sEtiq == "02") {
                $sPropio = substr($pNombre , $iPos , $iPos + $iLong);  //Nombre Propio
            }
            if ($sEtiq == "03") {
                $sSegundo =  substr($pNombre , $iPos , $iPos + $iLong);  //Segund Nombre
            }
            if ($sEtiq == "04") {
                $sFecha = substr($pNombre , $iPos, $iPos + $iLong); 	  //Fec Nacimiento
                $fFecNac = $this->convierte_fecha($sFecha);
            }
            if ($sEtiq == "05") {
                $sRFC = substr($pNombre , $iPos , $iPos + $iLong); 	  //RFC
            }
            if ($sEtiq == "08") {
                $sNac = substr($pNombre , $iPos , $iPos + $iLong); 	  //Nacionalidad
            }
            if ($sEtiq  == "11") {
                $sEdoCivil = substr($pNombre , $iPos , $iPos + $iLong); 	  //Estado Civil
            }

            if ($sEtiq == "12") {
                $sSexo = substr($pNombre , $iPos , $iPos + iLong); 	  //Sexo
            }
            if ($sEtiq  == "14") {
                $sElectoral = substr($pNombre , $iPos , $iPos + $iLong); 	  //IFE
            }
            if ($sEtiq  == "17") {
                $sDep = substr($pNombre , $iPos , $iPos + $iLong); 	  //Dependientes
            }
            if (($sEtiq == "PA") || ($sEtiq == "PE") || ($sEtiq == "TL") || ($sEtiq == "IQ")  || ($sEtiq  == "RS" )  {
                $iLong = $iPos - 4;
                $iPos = $xLong;
            }
            $iPos += $iLong;
        }

		$sInsert = "Insert into circulo_personas(folioconsultaotorgante, nombres, apellidopaterno, apellidomaterno, apellidoadicional, " +
        "fechanacimiento, rfc, nacionalidad, estadocivil, numerodependientes, sexo, claveife) values(" +
        $fol + ",'" + $sPropio + " " + $sSegundo + "','" +  $sPaterno + "','" + $sMaterno + "','" + $sAdicional + "','" + $fFecNac + "','" + $sRFC + "','" +
        $sNac + "','" + $sEdoCivil + "'," + $sDep + ",'" + $sSexo +"','" + $sElectoral +"')";
		try{
            // Logger.getAnonymousLogger().info(sInsert);
            $st = DB::insert($sInsert);            
        } catch(\Exception $e){
            // Logger.getAnonymousLogger().info(e.toString());
            // for( int i=0; i!= e.getStackTrace().length; i++) {
            // Logger.getAnonymousLogger().info(e.getStackTrace()[i].toString());
        }
		
        $iLong = -1;
		return $iLong;
	}

    /*
    *lee_dir
    */
	private  function lee_dir($pDir, $fol) {
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
            $sEtiq = strlen($pDir , $iPos , $iPos + 2);
            $iPos = $iPos + 4;
            $sLong = substr($pDir , $iPos-2 , $iPos);
            $iLong = $sLong; //iLong.parseInt(sLong);
            if ($sEtiq  == "PA") {
                if ($iPos == 4) {
                    $sDir1 = substr($pDir , $iPos , $iPos + $iLong);  //Domicilio 1
                    $sEtiq = "";
                }
            }
            if ($sEtiq == "00") {
                $sDir2 = substr($pDir , $iPos , $iPos + $iLong);  //Domicilio 2
            }
            if ($sEtiq  == "01") {
                $sCol = substr($pDir , $iPos , $iPos + $iLong);  //Colonia
            }
            if ($sEtiq  == "02") {
                $sMun = substr($pDir , $iPos , $iPos + $iLong);  //Municipio
            }
            if ($sEtiq  == "03") {
                $sCid = substr($pDir , $iPos , $iPos + $iLong);  //Ciudad
            }
            if ($sEtiq == "04") {
                $sEdo = substr($pDir , $iPos , $iPos + $iLong);  //Estado
            }
            if ($sEtiq  == "05") {
                $sCP = substr($pDir , $iPos , $iPos + $iLong);  //Codigo postal
            }
            if ($sEtiq == "06") {
                $sFecRes = substr($pDir , $iPos , $iPos + $iLong); 	  //Fecha Residencia
                $fFecRes = $this->convierte_fecha($sFecRes);
            }
            if ($sEtiq  == "07") {
                $sTel = substr($pDir , $iPos , $iPos + $iLong); 	  //Telefono
            }
            if ($sEtiq  == "12") {
                $sFecDom = substr($pDir , $iPos , $iPos + $iLong); 	  //Fecha Residencia
                $fFecDom = $this->convierte_fecha($sFecDom);
            }
            if (($sEtiq  == "PA" ) || ($sEtiq == "PE") || ($sEtiq  == "TL") || ($sEtiq  == "IQ")  || ($sEtiq  == "RS"))  {
                $iLong = $iPos - 4;
                $iPos = $xLong;
            }
            $iPos += $iLong;
        }

		$sInsert = "Insert into circulo_domicilios(folioconsultaotorgante, direccion, ciudad, estado, cp, fecharesidencia, telefono, delegacionmunicipio, coloniapoblacion, fecharegistrodomicilio) values(" +
        $fol + ",'" + ($sDir1 + " " + $sDir2) + "','" + $sCid + "','" + $sEdo + "'," + $sCP + ",'" + $fFecRes + "','" + $sTel + "','" + $sMun + "','" + $sCol + "','" + $fFecDom + "')";

		try{
            //Logger.getAnonymousLogger().info(sInsert);
            $st = DB::insert($sInsert);            
        } catch(\Exception $e) {
            // Logger.getAnonymousLogger().info(e.toString());
            // for( int i=0; i!= e.getStackTrace().length; i++) {
            //     Logger.getAnonymousLogger().info(e.getStackTrace()[i].toString());
            // }
			$iLong = -1;
		}

		return $iLong;
	}

    /*
    * lee_empleo
    */
	private function  lee_empleo($pEmp, $fol) {
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
		$Long = 0;
		$xLong = strlen($pEmp);

		$sEtiq = substr($pEmp , $iPos , $iPos + 2);
		if ($sEtiq  == "PE") {
            while ($iPos < $xLong) {
                $sEtiq = substr($pEmp , $iPos , $iPos + 2);
                $iPos = $iPos + 4;
                $sLong = substr($pEmp , $iPos - 2 , $iPos);
                $iLong =  $sLong; // iLong.parseInt(sLong);
                if ($sEtiq  == "PE") {
                    if ($iPos == 4) {
                        $sEmpleo = substr($pEmp, $iPos, $iPos + $iLong);  //Nombre Empleo
                        $sEtiq = "";
                    }
                }
                if ($sEtiq == "00") {
                    $sDir1 = substr($pEmp , $iPos , $iPos + $iLong);  //Domicilio 1
                }
                if ($sEtiq ==  "01") {
                    $sDir2 = substr($pEmp, $iPos , $iPos + $iLong);  //Domicilio 2
                }
                if ($sEtiq == "02") {
                    $sCol = substr($pEmp , $iPos , $iPos + $iLong);  //Colonia
                }
                if ($sEtiq == "03") {
                    $sMun = substr($pEmp , $iPos , $iPos + $iLong);  //Municipio
                }
                if ($sEtiq == "04" ) {
                    $sCid = substr($pEmp , $iPos , $iPos + $iLong);  //Ciudad
                }
                if ($sEtiq == "05") {
                    $sEdo = substr($pEmp , $iPos , $iPos + $iLong);  //Estado
                }
                if ($sEtiq == ("06")) {
                    $sCP = substr($pEmp , $iPos , $iPos + $iLong);  //Codigo postal
                }
                if ($sEtiq == ("07")) {
                    $sTel = substr($pEmp , $iPos, $iPos + $iLong); 	  //Telefono
                }
                if ($sEtiq == "10") {
                    $sPuesto = substr($pEmp , $iPos, $iPos + $iLong); 	  //Puesto
                }
                if ($sEtiq == "11") {
                    $sFecCon = substr($pEmp , $iPos , $iPos + $iLong);	//Fecha Contracion
                    $fFecCon = $this->convierte_fecha($sFecCon);
                }
                if ($sEtiq == "13") {
                    $sSalario = substr($pEmp , $iPos , $iPos + $iLong); 	  //Salario
                }
                if ($sEtiq == "16") {
                    $sFecEmp = substr($pEmp , $iPos , $iPos + $iLong);	//Fecha Ultimo dia
                    $fFecEmp = $this->convierte_fecha($sFecEmp);
                }
                if (($sEtiq == "PE") || ($sEtiq == "TL") || ($sEtiq == "IQ")  || ($sEtiq == "RS"))  {
                    $iLong = $iPos - 4;
                    $iPos = $xLong;
                }

                $iPos += $iLong;
            }
            $sInsert = "Insert into circulo_empleos(folioconsultaotorgante, direccion, coloniapoblacion, delegacionmunicipio, ciudad, estado, telefono, cp, nombreempresa, puesto, salariomensual, fechacontratacion, fechaultimadiaempleo) values(" +
                $fol + ",'" + ($sDir1 + " " + $sDir2) + "','" + $sCol + "','" + $sMun + "','" + $sCid + "','" + $sEdo + "','" + $sTel + "'," + $sCP + ",'" + $sEmpleo + "','" + $sPuesto + "'," + $sSalario + ",'" + $fFecCon + "','" + $fFecEmp +  "')";
    		try{

                //Logger.getAnonymousLogger().info(sInsert);
                $st = DB::insert($sInsert)
                
            } catch(\Exception $e) {
                    // Logger.getAnonymousLogger().info(e.toString());
                    // for( int i=0; i!= e.getStackTrace().length; i++) {
                    //     Logger.getAnonymousLogger().info(e.getStackTrace()[i].toString());
                    // }
    			$iLong = -1;
    		}
		} 
        else
        {
            $fFecCon = $this->convierte_fecha($sFecCon);
            $fFecEmp = $this->convierte_fecha($sFecEmp);

            $sInsert = "Insert into circulo_empleos(folioconsultaotorgante, direccion, coloniapoblacion, delegacionmunicipio, ciudad, estado, telefono, cp, nombreempresa, puesto, salariomensual, fechacontratacion, fechaultimadiaempleo) values(" +
            $fol + ",'" + ($sDir1 + " " + $sDir2) + "','" + $sCol + "','" + $sMun + "','" + $sCid + "','" + $sEdo + "','" + $sTel + "'," + 
            $sCP + ",'" + $sEmpleo + "','" + $sPuesto + "'," + $sSalario + ",'" + $fFecCon + "','" + $fFecEmp +  "')";
			try{

                //Logger.getAnonymousLogger().info(sInsert);
                st = DB::insert($sInsert)
                
            } catch(\Exception $e) {
                // Logger.getAnonymousLogger().info(e.toString());
                // for( int i=0; i!= e.getStackTrace().length; i++) {
                //     Logger.getAnonymousLogger().info(e.getStackTrace()[i].toString());
                // }
    			$iLong = -1;
    		}
    			$iLong = 0;
		}

		return $iLong;
	}

    /*
    *lee_cuentas
    */
	private function lee_cuentas($pCuenta , $fol) {
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
		$fFecAct = new DateTime(0,9,9);
		$fFecAper = new DateTime(0,9,9);
		$fFecUlt = new DateTime(0,9,9);
		$fFecUltc = new DateTime(0,9,9);
		$fFecComp = new DateTime(0,9,9);
		$fFecCierre = new DateTime(0,9,9);
		$fFecRep = new DateTime(0,9,9);
		$fFecPeor = new DateTime(0,9,9);
		$fFecRec = new DateTime(0,9,9);
		$fFecAnt = new DateTime(0,9,9);
		$iPos = 0;
		$iLong = 0;
		$xLong = strleng($pCuenta);
		while ($iPos < $xLong) {
            $sEtiq = substr($pCuenta , $iPos , $iPos + 2);
            $iPos = $iPos + 4;
            $sLong = substr($pCuenta , $iPos - 2 , $iPos);
            $iLong = (int)$sLong ; //iLong.parseInt(sLong);
            if ($sEtiq == "TL") {
                if ($iPos == 4) {
                    $sFecAct = substr($pCuenta , $iPos , $iPos + $iLong);  //Fecha de Actualizacion
                    $fFecAct = $this->convierte_fecha($sFecAct);
                    $sEtiq = "";
                }
            }
            if ($sEtiq == "00") {
                $sImp = substr($pCuenta , $iPos, $iPos + $iLong);  	//Impugnado por el cliente
            }
            if ($sEtiq == "01") {
                $clOtor = substr($pCuenta , $iPos , $iPos + $iLong);  	//Clave Otorgante
            }
            if ($sEtiq == "02") {
                $nomOtor = substr($pCuenta , $iPos , $iPos + $iLong);  //Nombre Otorgante
            }
            if ($sEtiq == "03") {
                $telOtor = substr($pCuenta , $iPos , $iPos + $iLong);  //Telefono Otorgante
            }
            if ($sEtiq == "04") {
                $sCuenta = substr($pCuenta , $iPos , $iPos + $iLong);  	//Cuenta
            }
            if ($sEtiq == "05") {
                $sResp = substr($pCuenta , $iPos , $iPos + $iLong);  	//Tipo de Responsabilidad
                if($sResp == "I")
                    $sResp = "INDIVIDUAL";
                if($sResp == "J")
                    $sResp = "MANCOMUNADO";
                if($sResp == "C")
                    $sResp = "OBLIGADO SOLIDARIO";
            }
            if ($sEtiq == "06") {
                $sTipo = substr($pCuenta , $iPos , $iPos + $iLong);  	//Tipo de Cuenta
                if($sTipo == "I")
                    $sTipo = "PAGOS FIJOS";
                if($sTipo == "M")
                    $sTipo = "HIPOTECA";
                if($sTipo == "O")
                    $sTipo = "SIN LIMITE ESTABLECIDO";
                if($sTipo == "R")
                    $sTipo = "REVOLVENTE";
                // Logger.getAnonymousLogger().info(sTipo + " " + sTipo.length());
            }
            if ($sEtiq == "07") {
                $sContrato = substr($pCuenta , $iPos , $iPos + $iLong);	//Tipo de contrato
                $sTContrato = $sContrato;
                if($sContrato == "AF")
                    $sContrato = "APARATOS/MUEBLES";
                if($sContrato == "AG")
                    $sContrato = "AGROPECUARIO (PFAE)";
                if($sContrato == "AL")
                    $sContrato = "ARRENDAMIENTO AUTOMOTRIZ";
                if($sContrato == "AP")
                    $sContrato="AVIACION";
                if($sContrato == "AU")
                    $sContrato = "COMPRA DE AUTOMOVIL";
                if($sContrato == ("BD"))
                    $sContrato = "FIANZA";
                if($sContrato == ("BT"))
                    $sContrato="BOTE/LANCHA";
                if($sContrato == ("CC"))
                    $sContrato = "TARJETA DE CREDITO";
                if($sContrato == ("CE"))
                    $sContrato = "CARTAS DE CREDITO (PFAE)";
                if($sContrato == ("CF"))
                    $sContrato = "CREDITO FISCAL";
                if($sContrato == ("CL"))
                    $sContrato = "LINEA DE CREDITO";
                if($sContrato == ("CO"))
                    $sContrato = "CONSOLIDACION";
                if($sContrato == ("CS"))
                    $sContrato = "CREDITO SIMPLE (PFAE)";
                if($sContrato == ("CT"))
                    $sContrato = "CON COLATERAL";
                if($sContrato == ("DE"))
                    $sContrato = "DESCUENTOS (PFAE)";
                if($sContrato == ("EQ"))
                    $sContrato = "EQUIPO";
                if($sContrato == ("FI"))
                    $sContrato="FIDEICOMISOS (PFAE)";
                if($sContrato == ("FT"))
                    $sContrato="FACTORAJE";
                if($sContrato == ("HA"))
                    $sContrato="HABILITACION O AVIO (PFAE)";
                if(4sContrato == ("HE"))
                    $sContrato="PRESTAMO TIPO *HOME EQUITY*";
                if(4sContrato == ("HI"))
                    $sContrato="MEJORAS A LA CASA";
                if($sContrato == ("LS"))
                    $sContrato="ARRENDAMIENTO";
                if($sContrato == ("MI"))
                    $sContrato="OTROS";
                if($sContrato == ("OA"))
                    $sContrato="OTROS ADEUDOS VENCIDOS (PFAE)";
                if(4sContrato == ("PA"))
                    $sContrato="PRESTAMOS PARA PERSONAS FISICAS CON ACTIVIDAD EMPRESARIAL PFAE)";
                if(4sContrato == ("PB"))
                    $sContrato="EDITORIAL";
                if($sContrato == ("PG"))
                    4sContrato="PGUE (PRESTAMO CON GARANTIAS DE UNIDADES INDUSTRIALES)(PFAE)";
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
            if($sEtiq == "08"){  //if(sEtiq.equalsIgnoreCase("08")){
                $sMoneda = substr($pCuenta , $iPos , $iPos + $iLong);  //Moneda
                if($sMoneda != "MX" || $sMoneda != "US" || $sMoneda != "UD")
                    $sMoneda ="MX";
            }
            if ($sEtiq == ("10")) {
                $sNumPagos = substr($pCuenta , $iPos , $iPos + $iLong);	//Numero de pagos
            }
            if ($sEtiq == ("11")) {
                $sFrecPagos = substr($pCuenta  , $iPos , $iPos + $iLong); //Fecuencia de pagos
                if($sFrecPagos == ("B"))
                    $sFrecPagos = "BIMESTRAL";
                if($sFrecPagos == ("D"))
                    $sFrecPagos = "DIARO";
                if(4sFrecPagos == ("H"))
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
                $sMonto = substr($pCuenta , $iPos , $iPos + $iLong);	//Monto a Pagar
            }
            if ($sEtiq == ("13")) {
                $sFecAper = substr($pCuenta , $iPos , $iPos + $iLong);	//Fecha apertura
                $fFecAper = $this->convierte_fecha($sFecAper);
            }
            if ($sEtiq == ("14")) {
                $sFecUlt = substr($pCuenta , $iPos , $iPos + $iLong);	//Fecha ultimo pago
                $fFecUlt = $this->convierte_fecha($sFecUlt);
            }
            if ($sEtiq == ("15")) {
                $sFecUltc = substr($pCuenta , $iPos , $iPos + $iLong);	//Fecha ultima compra
                $fFecUltc = $this->convierte_fecha($sFecUltc);
            }
            if ($sEtiq == ("16")) {
                $sFecCierre = substr($pCuenta , $iPos , $iPos + $iLong);	//Fecha cierre
                $fFecCierre = $this->convierte_fecha($sFecCierre);
                //System.out.println("esta es la f:" + fFecCierre);
            }
            if ($sEtiq == ("21")) {
                $sCremax = substr($pCuenta , $iPos , $iPos + $iLong);	//Saldo Actual
                $car = substr($sCremax , strlen(sCremax)-1, strlen(sCremax)); //sCremax.charAt(sCremax.length() - 1);
				if($car == '+'  || $car == '-')
                {
                    $sCremax = substr($pCuenta , $iPos , $iPos + $iLong - 1);
                    if($car == '-')
                        $sCremax = "-" + $sCremax;
                }
			}
            if ($sEtiq == ("22")) {
                $sSaldo = substr($pCuenta , $iPos , $iPos + $iLong);	//Saldo Actual
                $car = substr($sSaldo , strlen(sSaldo) - 1 , strlen(sSaldo)); //sSaldo.charAt(sSaldo.length() - 1);
				if($car == '+'  || $car == '-')
                {
                    $sSaldo = substr($pCuenta , $iPos , $iPos + $iLong - 1);
                    if($car == '-')
                        $sSaldo = "-" + $sSaldo;
                }
			}
            if ($sEtiq == ("23")) {
                $sLimite = substr($pCuenta , $iPos , $iPos + $iLong);//Saldo Vencido
            }
            if ($sEtiq == ("24")) {
                $sVencido = substr($pCuenta , $iPos , $iPos + $iLong);	//Saldo Vencido
            }
            if ($sEtiq == ("25")) {
                $sNumVenc = substr($pCuenta , $iPos , $iPos + $iLong);	//Num Pagos Vencidos
            }
            if ($sEtiq == ("26")) {
                $sMOP = substr($pCuenta  , $iPos , $iPos + $iLong);		//Forma Pago
                if($sMOP == ("UR"))
                    $sMOP+= "=CUENTA SIN INFORMACION";
                if($sMOP == ("00"))
                    $sMOP+= "=MUY RECIENTE PARA SER INFORMADA";
                if($sMOP == ("01"))
                    $sMOP+= "=CUENTA AL CORRIENTE";
                if($sMOP == ("02"))
                    $sMOP+= "=ATRASO DE 01 A 29 DIAS";
                if($sMOP == ("03"))
                    $sMOP+= "=ATRASO DE 30 A 59 DIAS";
                if($sMOP == ("04"))
                    $sMOP+= "=ATRASO DE 60 A 89 DIAS";
                if($sMOP == ("05"))
                    $sMOP+= "=ATRASO DE 90 A 119 DIAS";
                if($sMOP == ("06"))
                    $sMOP+= "=ATRASO DE 120 A 149 DIAS";
                if($sMOP == ("07"))
                    $sMOP+= "=ATRASO DE 150 A 12 MESES";
                if($sMOP == ("96"))
                    $sMOP+= "=ATRASO DE 12 MESES";
                if($sMOP == ("97"))
                    $sMOP+= "=CUENTA CON DEUDA PARCIAL O TOTAL SIN RECUPERAR";
                if($sMOP == ("99"))
                    $sMOP+= "=FRAUDE COMETIDO POR EL CLIENTE";
            }
            if($sEtiq == ("27")){
                $sHistorial = substr($pCuenta , $iPos , $iPos + $iLong); //Historial
                //$Logger.getAnonymousLogger().info("Historial = "+ sHistorial);
            }
            if ($sEtiq == ("28")) {
                $sFecRec = substr($pCuenta , $iPos , $iPos + $iLong);	//Fecha cierre
                $fFecRec = $this->convierte_fecha($sFecRec);
            }
            if($sEtiq == ("29")){
                $sFecAnt = substr($pCuenta , $iPos , $iPos + $iLong);	//Fecha cierre
                $fFecAnt = $this->convierte_fecha($sFecAnt);
            }
            if ($sEtiq == ("30")) {
                $sObs = substr($pCuenta , $iPos , $iPos + $iLong);		//Observacion
                if($sObs == ("AD"))
                    $sObs += "=CUENTA EN DISPUTA";
                if($sObs == ("CA"))
                    $sObs += "=CUENTA AL CORRIENTE VENDIDA";
                if($sObs == ("CC"))
                    $sObs += "=CUENTA CERRADA POR EL CONSUMIDOR";
                if($sObs == ("CI"))
                    $sObs += "=CANCELADA POR INACTIVIDAD";
                if($sObs == ("CM"))
                    $sObs += "=CANCELADA POR EL OTORGANTE";
                if($sObs == ("CL"))
                    $sObs+="=CUENTA EN COBRANZA PAGADA TOTALMENTE";
                if($sObs == ("CP"))
                    $sObs += "=CARTERA VENCIDA";
                if($sObs == ("CR"))
                    $sObs += "=DACION EN RENTA";
                if($sObs == ("CV"))
                    $sObs += "=CUENTA VENCIDA VENDIDA";
                if($sObs == ("CZ"))
                    $sObs += "=CANCELADA CON SALDO CERO";
                if($sObs == ("DP"))
                    $sObs += "=PAGOS DIFERIDOS";
                if($sObs == ("DR"))
                    $sObs += "=DISPUTA RESUELTA, CONSUMIDOR INCONFORME";
                if($sObs == ("FD"))
                    $sObs += "=CUENTA FRAUDULENTA";
                if($sObs == ("FN"))
                    $sObs += "=CUENTA FRAUDULENTA NO ATRIBUIBLE AL CONSUMIDOR";
                if($sObs == ("FP"))
                    $sObs +="=CANCELACION DE ADJUDICACION DE INMUEBLE POR PAGO";
                if($sObs == ("FR"))
                    $sObs +="=ADJUDICACION DE INMUEBLE EN PROCESO";
                if($sObs == ("IA"))
                    $sObs +="=CUENTA INACTIVA";
                if($sObs == ("IR"))
                    $sObs +="=ADJUDICACION INVOLUNTARIA";
                if($sObs == ("LC"))
                    $sObs +="=QUITA POR IMPORTE MENOR ACORDADA CON EL CONSUMIDOR";
                if($sObs == ("LG"))
                    $sObs +="=QUITA POR IMPORTE MENOR POR PROGRAMA INSTITUCIONAL";
                if($sObs == ("LS"))
                    $sObs +="=TARJETA DE CREDITO EXTRAVIADA O ROBADA";
                if($sObs == ("MD"))
                    $sObs +="=PAGO PARCIAL EFECTUADO A CUENTA IRRECUPERABLE";
                if($sObs == ("NA"))
                    $sObs +="=CUENTA AL CORRIENTE VENDIDA A UN NO USUARIO DE BC";
                if($sObs == ("NV"))
                    $sObs +="=CUENTA VENCIDA VENDIDA A UN NO USUARIO DE BC";
                if($sObs == ("PC"))
                    $sObs+="=ENVIADO A DESPACHO DE COBRANZA";
                if($sObs == ("PD"))
                    $sObs+="=ADJUDICACION CANCELADA POR PAGO";
                if($sObs == ("PL"))
                    $sObs += "=LIMITE EXCEDIDO";
                if($sObs == ("PS"))
                    $sObs+="=SUSPENSION DE PAGO";
                if($sObs == ("RA"))
                    $sObs+="=CUENTA AL CORRIENTE RESTRUCTURADA POR PROGRAMA INSTITUCIONAL";
                if($sObs == ("RC"))
                    $sObs+="=CUENTA AL CORRIENTE RESTRUCTURADA ACORDADA CON EL CONSUMIDOR";
                if($sObs == ("RE"))
                    $sObs+="=CUENTA AL CORRIENTE RESTRUCTURADA PAGADA TOTALMENTE";
                if($sObs == ("RF"))
                    $sObs+="=REFINANCIADA";
                if($sObs == ("RO"))
                    $sObs+="=CUENTA VENCIDAD RESTRUCTURADA POR PROGRAMA INSTITUCIONAL";
                if($sObs == ("RR"))
                    $sObs+="=RESTITUCION DEL BIEN";
                if($sObs == ("RV"))
                    $sObs+="=CUENTA VENCIDA RESTRUCTURADA ACORDADA CON EL CONSUMIDOR";
                if($sObs == ("SC"))
                    $sObs+="=DEMANDA RESUELTA EN FAVOR DEL CONSUMIDOR";
                if($sObs == ("SG"))
                    $sObs+="=DEMANDA POR EL OTORGANTE";
                if($sObs == ("SP"))
                    $sObs+="=DEMANDA RESUELTA A FAVOR DEL OTORGANTE";
                if($sObs == ("ST"))
                    $sObs+="=ACUERDO POR IMPORTE MENOR";
                if($sObs == ("SU"))
                    $sObs+="=DEMANDA POR EL CONSUMIDOR";
                if($sObs == ("TC"))
                    $sObs+="=SUSTICION DE DEUDOR";
                if($sObs == ("TL"))
                    $sObs+="=TRANSFERENCIA A NUEVO OTORGANTE";
                if($sObs == ("TR"))
                    $sObs+="=TRANSFERIDA A OTRA AREA";
                if($sObs == ("UP"))
                    $sObs+="=CUENTA QUE CAUSA QUEBRANTO";
                if($sObs == ("VR"))
                    $sObs+="=DACION EN PAGO";
            }
            if ($sEtiq == ("37")) {
                $sFecPeor = substr($pCuenta , $iPos , $iPos + $iLong);		//Fecha peor atraso
                $fFecPeor = $this->convierte_fecha($sFecPeor);
            }
            if ($sEtiq == ("38")) {
                $sMOP2 = substr($pCuenta , $iPos , $iPos + $iLong);		//Forma Pago
                if($sMOP2 == ("UR"))
                    $sMOP2+= "=CUENTA SIN INFORMACION";
                if($sMOP2 == ("00"))
                    $sMOP2+= "=MUY RECIENTE PARA SER INFORMADA";
                if($sMOP2 == ("01"))
                    $sMOP2+= "=CUENTA AL CORRIENTE";
                if($sMOP2 == ("02"))
                    $sMOP2+= "=ATRASO DE 01 A 29 DIAS";
                if($sMOP2 == ("03"))
                    $sMOP2+= "=ATRASO DE 30 A 59 DIAS";
                if($sMOP2 == ("04"))
                    $sMOP2+= "=ATRASO DE 60 A 89 DIAS";
                if($sMOP2 == ("05"))
                    $sMOP2+= "=ATRASO DE 90 A 119 DIAS";
                if($sMOP2 == ("06"))
                    $sMOP2+= "=ATRASO DE 120 A 149 DIAS";
                if($sMOP2 == ("07"))
                    $sMOP2+= "=ATRASO DE 150 A 12 MESES";
                if($sMOP2 == ("96"))
                    $sMOP2+= "=ATRASO DE 12 MESES";
                if($sMOP2 == ("97"))
                    $sMOP2+= "=CUENTA CON DEUDA PARCIAL O TOTAL SIN RECUPERAR";
                if($sMOP2 == ("99"))
                    $sMOP2+= "=FRAUDE COMETIDO POR EL CLIENTE";
            }
            if (($sEtiq == ("TL")) || ($sEtiq == ("IQ"))  || ($sEtiq == ("RS")))  {
                $iLong = $iPos - 4;
                $iPos = $xLong;
            }
            $iPos += $iLong;

        }
		if(empty($sLimite) || $sLimite  == ""){
            $sLimite = "0";
        }
		// Logger.getAnonymousLogger().info(sLimite);

		
		$sInsert = "Insert into Circulo_cuentas(folioconsultaotorgante, fechaactualizacion, registroimpugnado, claveotorgante, nombreotorgante, " +
        "tiporesponsabilidad, tipocuenta, numeropagos, frecuenciapagos, fechaaperturacuenta, fechaultimopago, fechacierrecuenta, " +
        "saldoactual, saldovencido, numeropagosvencidos, historicopagos,claveunidadmonetaria, pagoactual, fechapeoratraso," +
        "saldovencidopeoratraso,limitecredito, montopagar, tipocredito, peoratraso, observacion, creditomaximo, fechaultimacompra, " +
        "fecharecientehistoricopagos, fechaantiguahistoricopagos, tipo_credito_id) values(" +
        $fol + ",'" + $fFecAct + "','" + $sImp + "','" + $clOtor + "','" + $nomOtor +
        "','" + $sResp + "','" + $sTipo + "'," + $sNumPagos + ",'" + $sFrecPagos + "','" + $fFecAper + "','" + $fFecUlt
        + "','" + $fFecCierre + "'," + $sSaldo + "," + $sVencido + "," + $sNumVenc + ",'" + $sHistorial + "','" + $sMoneda + "','"
        + $sMOP + "','" + $fFecPeor + "','" + $sSaldov + "'," + $sLimite + "," + $sMonto + ",'" + $sContrato + "','" + $sMOP2 +
        "','" + $sObs +"'," + $sCremax +",'" + $fFecUltc +"','" + $fFecRec + "','" + $fFecAnt + "','" + $sTContrato + "')";

		try{
            //Logger.getAnonymousLogger().info(sInsert);
            $st = DB::insert($sInsert)            
        } catch(\Exception $e) {
            // Logger.getAnonymousLogger().info(e.toString());
            // for( int i=0; i!= e.getStackTrace().length; i++) {
            //     Logger.getAnonymousLogger().info(e.getStackTrace()[i].toString());
            // }
			$iLong = -1;
		}
		return $iLong;
	}
  
    /*
    *lee_consultas
    */
	private  function lee_consultas($pConsultas, $fol) {
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
//		Logger.getAnonymousLogger().info(pDir + " " + xLong);
		while ($iPos < $xLong) {
            $sEtiq = substr($pConsultas , $iPos , $iPos + 2);
            $iPos = $iPos + 4;
            $sLong = substr($pConsultas , $iPos - 2 , $iPos);
            $iLong = (int)$sLong ; iLong.parseInt(sLong);
            if ($sEtiq == ("IQ")) {
                if ($iPos == 4) {
                    $sFecCons = substr($pConsultas , $iPos , $iPos + $iLong);  //Fecha de Actualizacion
                    $fFecCons = $this->convierte_fecha($sFecCons);
                    $sEtiq = "";
                }
            }
            if ($sEtiq == ("00")) {
                $sBuro = substr($pConsultas , $iPos , $iPos + $iLong);  	//Impugnado por el cliente
            }
            if ($sEtiq == ("01")) {
                $clOtor = substr($pConsultas , $iPos , $iPos + $iLong);  	//Clave Otorgante
            }
            if ($sEtiq == ("02")) {
                $nomOtor = substr($pConsultas , $iPos , $iPos + $iLong);  //Nombre Otorgante
            }
            if ($sEtiq == ("03")) {
                $telOtor = substr($pConsultas , $iPos , $iPos + $iLong);  //Telefono Otorgante
            }
            if ($sEtiq == ("04")) {
                $sContrato = substr($pConsultas  , $iPos , $iPos + $iLong);	//Tipo de contrato
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
                $sMoneda = substr($pConsultas , $iPos , $iPos + $iLong);	//Moneda
                if($sMoneda != "MX" || $sMoneda != "US" || $sMoneda != "UD")
                    $sMoneda ="MX";
            }
            if ($sEtiq == ("06")) {
                $sMonto = substr($pConsultas , $iPos , $iPos + $iLong);	//Monto del contrato
            }
            if ($sEtiq == ("07")) {
                $sResp = substr($pConsultas , $iPos , $iPos + $iLong);			//Tipo de Responsabilidad
                if($sResp == ("I"))
                    $sResp = "INDIVIDUAL";
                if($sResp == ("J"))
                    $sResp = "MANCOMUNADO";
                if($sResp == ("C"))
                    $sResp = "OBLIGADO SOLIDARIO";
            }
            if ($sEtiq == ("08")) {
                $sConsumidor = substr($pConsultas , $iPos , $iPos + $iLong);	//Nuevo  consumidor
            }
            if (($sEtiq == ("IQ"))  || ($sEtiq == ("RS")))  {
                $iLong = $iPos - 4;
                $iPos = $xLong;
            }

            $iPos += $iLong;
        }
		$sInsert = "Insert into circulo_consultas_efectuadas(folioconsultaotorgante, fechaconsulta, claveotorgante, nombreotorgante, tipocredito, importecredito, tiporesponsabilidad, claveunidadmonetaria) values(" +
        $fol + ",'" +  $fFecCons + "','" + $clOtor + "','" + $nomOtor + "','" + $sContrato + "'," + $sMonto  + ",'" + $sResp +"','" +  
        $sMoneda+ "')";
		try{
            //Logger.getAnonymousLogger().info(sInsert);
            $st = DB::insert($sInsert);            
        }
        catch(\Exception $e)
        {
            // Logger.getAnonymousLogger().info(e.toString());
            // for( int i=0; i!= e.getStackTrace().length; i++) {
            //     Logger.getAnonymousLogger().info(e.getStackTrace()[i].toString());
            // }
			$iLong = -1;
		}


		return $iLong;
	}

    /*
    *lee_fin
    */
	private  function lee_fin($cad)
	{
        return substr($cad , strlen($cad)-15, strlen($cad)-6);
    }

    /*
    *convierte_fecha
    */
	private function  convierte_fecha ($xFecha){
        $fFecha = new DateTime(1988,8,8);
		return $fFecha = substr($xFecha , 4 ,8) . "-" . subtr($xFecha , 2 ,4) . "-" . substr($xFecha , 0,2) ;
	}
}

}
