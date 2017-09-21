<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Faker\Provider\zh_TW\DateTime;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;

use DB;
use Response;
use Log;


class BuroPMImplController extends Controller
{
    

	private $iFolioid = 0;
	private $numRegistros = 0;
	private $idSegmentoHD = 0;//	long idSegmentoHD = 0L;
	private $segmentos = null; //HashMap<String, String> segmentos = null;
	private $idError;
	private $descError;
	private $user;
	private $pass;
	private $code;

	/**********customize********/
	function startsWith($haystack, $needle)
    {
        $length = strlen($needle);
        return (substr($haystack, 0, $length) === $needle);
    }

    function endsWith($haystack, $needle)
    {
        $length = strlen($needle);

        return $length === 0 ||
        (substr($haystack, -$length) === $needle);
    }

	/******************/
	
	public function consulta($conn, $Solicitante, $noEtapa, $valorEtapa, $ProductoRequerido,
			$TipoCuenta, $ClaveUnidadMonetaria, $ImporteContrato, $usuario_id_sol, $usuario_id_aut,
            $computadora, $confirma, $sucursal) {
		$folio = -1;
		$xConsulta = "";
		$out = null ; //DataOutputStream out = null;
	    $in = null ; //DataInputStream in = null;
		Log::info($Solicitante);
		//$host = "128.9.55.29";
		//$port = 9004;
		/* "9992", "9992JUAREZH", "fg57Y8jW" */
		//Socket buroSocket;
		$xrespuestaBuro  = "";
		//StringBuffer sb;
		$aux = "";

		try {
			try{
				// INICIALIZAMOS
				$this->idError = 0;
				$this->descError = "";

				 $xConsulta = $this->genera_consultaPM(Solicitante);
				//Quitamos la letra � de la cadena de consulta para que no marque error en buro.
				 $xConsulta = str_replace("�", "N", $xConsulta);
				 Log::info("------------------------> Consulta: \n" . $xConsulta);

				 $buroSocket = fsockopen(HOST, PORT, $errno, $errstr);
					$path = '/cms/test';
					$buffer = '';
					$method = "Get";
					$data = "consulta=".$xConsulta;
					$method = strtoupper($method);
					$host = HOST;
					if ($method = "GET") {
						$path .= '?' . $data;
					}
					if ($buroSocket) {
						$out = "$method $path HTTP/1.1\r\n";
						$out .= "Host: $host\r\n";
						$out .= "User-Agent: " . $_SERVER['HTTP_USER_AGENT'] . "\r\n";
						$out .= "Content-type: application/x-www-form-urlencoded\r\n";
						$out .= "Content-length: " . strlen($data) . "\r\n";
						$out .= "Connection: close\r\n\r\n";
						$out .= "$data\r\n\r\n";

						fwrite($buroSocket, $out);
						while (!feof($buroSocket)) {
							$line = fgets($buroSocket, 1024);
							$buffer .= $line;
						}
						fclose($buroSocket);
					}

//	             try {
//	            	 Log::info("empieza lectura");
//				     $car = 0;
//				     while ((car = in.read()) >= 0) {
//				    	 if (car == '\u0013') break;
//				    	 sb.append((char) car);
//				     }
//	             } catch( \Exception $e ) {
//	            	 Log::info($e);
//	            	 /*for ($i=0; i != e.getStackTrace().length; i++)
//	            		 Logger.getAnonymousLogger().info(e.getStackTrace()[i].toString());*/
//	             }

				//xrespuestaBuro = sb.toString();
				$xrespuestaBuro = $buffer;
	            Log::info("termina lectura");
				Log::info("------------------------> Regreso: \n" . $xrespuestaBuro);

	            Log::info("Guardamos consulta y respuesta");
				$folio = $this->inserta_tabla_consultacirculo($xConsulta, $xrespuestaBuro, $Solicitante, $noEtapa , $valorEtapa, $ProductoRequerido,
						$TipoCuenta, $ClaveUnidadMonetaria, $ImporteContrato, $usuario_id_sol, $usuario_id_aut, $computadora, $confirma, $sucursal, "ok");
			} catch(\Exception $se) {
				Log::info($se);
				$folio = -1;
	            $folio = $this->inserta_tabla_consultacirculo($xConsulta, $xrespuestaBuro, $Solicitante, $noEtapa , $valorEtapa, $ProductoRequerido,
	            		$TipoCuenta, $ClaveUnidadMonetaria, $ImporteContrato, $usuario_id_sol, $usuario_id_aut, $computadora, $confirma, $sucursal, $se);
			}
		} catch( \Exception $se) {
			Log::info($se);
			$folio = -1;
	        $folio = $this->inserta_tabla_consultacirculo($xConsulta, $xrespuestaBuro, $Solicitante, $noEtapa , $valorEtapa, $ProductoRequerido,
	        		$TipoCuenta, $ClaveUnidadMonetaria, $ImporteContrato, $usuario_id_sol, $usuario_id_aut, $computadora, $confirma, $sucursal, $se);
		}
		return $folio;
	}
	
	public function respuesta($conn, $sfolio) {
		$segmentos = null; //List<String> segmentos = null;
		$sTexto = "";
		$query = "";
		$result = 0;
		$errorIntr = false; //boolean errorIntr = false;
		
		try{ 
			// INICIALIZAMOS
			$this->idError = 0;
			$this->descError = "";			
			
			if (! $this->cleanFolio($sfolio)) {
				throw new Exception($this->idError . ": " . $this->descError);
			}
		    
		    $query = "SELECT respuestaxml FROM consultas_circulo WHERE folioconsulta = '" . $sfolio . "'";
		    $res2 = DB::select($query);			
			if(empty($res2)) return;

			$sTexto = trim(reset($res2));				
			$segmentos = $this->getSegmentos($sTexto);
			
			if ("" == trim($sTexto) || empty($segmentos)) {
				$this->idError = -5;
				$this->descError = "No existe respuesta de buro registrada";
				throw new Exception($this->idError . ": " . $this->descError);
			}
			
			$etiqueta = "";
			foreach($segmentos as $segmento ) {
				$etiqueta = substr($segmento , 0 , 2);
				
				if ("HD" == $etiqueta) {
				    Log::info("Procesando: " . $segmento);
                    $this->procesa_HD($segmento, $sfolio);
                } else if ("EM" == $etiqueta) {
                    Log::info("Procesando: " . $segmento);
                    $this->procesa_EM($segmento, $sfolio); 
                    $this->procesa_EM_OLD($sfolio, $segmento);
                } else if ("HC" == $etiqueta) {
                    Log::info("Procesando: " . $segmento);
                    $this->procesa_HC(segmento, sfolio);
                } else if ("HR" == $etiqueta) {
                    Log::info("Procesando: " . $segmento);
                    $this->procesa_HR($segmento, $sfolio);
                } else if ("DC" == $etiqueta) {
                    Log::info("Procesando: " . $segmento);
                    $this->procesa_DC($segmento, $sfolio);
                } else if ("AC" == $etiqueta) {
                    Log::info("Procesando: " . $segmento);
                    $this->procesa_AC($segmento, $sfolio);
                } else if ("CR" == $etiqueta) {
                    Log::info("Procesando: " . $segmento);
                    $this->procesa_CR($segmento, $sfolio);
                    $this->procesa_CR_OLD($sfolio, $segmento);
                } else if ("HI" == $etiqueta) {
                    Log::info("Procesando: " . $segmento);
                    $this->procesa_HI($segmento, $sfolio);
                } else if ("CO" == $etiqueta) {
                    Log::info("Procesando: " . $segmento);
                    $this->procesa_CO($segmento, $sfolio);
                } else if ("FD" == $etiqueta) {
                    Log::info("Procesando: " . $segmento);
                    $this->procesa_FD($segmento, $sfolio);
                } else if ("CN" == $etiqueta) {
                    Log::info("Procesando: " . $segmento);
                } else if ("ER" == $etiqueta) {
                    Log::info("Procesando: " . $segmento);
                    $this->procesa_ER($segmento, $sfolio); //Metodo para interpretar el error this.descError
                    $errorIntr = true;
                } else if ("CI" == $etiqueta) {
                    Log::info("Procesando: " . $segmento);
                    $this->procesa_CI($segmento, $sfolio);
                    $this->procesa_CI_OLD($sfolio, $segmento);
                }
				
				//if ($this ->idError != 0L && $errorIntr == false) throw new Exception (this.idError . ": " . this.descError);
			}//En base a la respuesta se procesa si hay un detalle en lo que devolvi� buro
			if ($errorIntr == true) {
				$this->idError = -100;
				$errAux = split("",$this->descError);				
				$this->descError = "Error en la etiqueta " . $errAux[3] . $errAux[4] . " del segmento " . errAux[1] . $errAux[2] . " de la consulta.";
			}
			$this->actualizaError_consultacirculo($sfolio, true);
			$result = (int)$sfolio;
		} catch(\Exception $e) {
			Log::info($e);
			$this->actualizaError_consultacirculo($sfolio, true);
			$result = -1;
		} 
		
		return $result;
	}

	// ----------------------------------------------------------------------------------
	// CONSULTA 
	// ----------------------------------------------------------------------------------
	
	private function genera_consultaPM($nSolicitante) {
		$header = "";
		$datos  = "";
		$cierre = "";
		$identificadorConsulta = 0;
		$solicitantes = "";
		

		// generamos el IN para consulta con los clientes que se requieran
		$this->numRegistros = 0;
		if (strpos(trim($nSolicitante), ',') !== false) {
			$splitted = split(",", trim($nSolicitante) );
			for ($i = 0; $i < count($splitted); $i++) {
				if ( "" !=  trim($splitted[$i])) {
					if ( "" != $solicitantes) $solicitantes .= ",";
					$solicitantes .= "'" . trim($splitted[$i]) . "'";
					$this->numRegistros += 1;
				}
			}
		} else {
			$solicitantes = "'" . $nSolicitante . "'";
			$this->numRegistros = 1;
		}
		
		// Generamos el ID de la consulta
		$identificadorConsulta = $this->generaIdentificadorConsulta();

		// Usuario y contrase�a anterior: "MC27341032", "tHFaJIks" -- PM: "9992", "9992JUAREZH", "fg57Y8jW"
		$header = $this->genera_HD($identificadorConsulta, $this->code, $this->user, $this->pass);
		$datos  = $this->genera_EM($solicitantes);
		$cierre = $this->genera_CI($identificadorConsulta);
		
		if ("" == $header) {
			$this->idError = -3;
			$this->descError = "Error al generar consulta - No se pudo generar el segmento encabezado HD";
			return "";
		}
		
		if ("" == ($datos)) {
			$this->idError = -3;
			$this->descError = "Error al generar consulta - No se pudo generar el segmento de datos EM";
			return "";
		}
		
		return $header . $datos . $cierre;
	}
	
	/*
	*genera_HD
	*/
	private  function genera_HD($identificadorConsulta, $codigoInstitucion, $clave, $pass) {
		$encabezado = "";
			
		try {
			$encabezado .= $this->campo("HD", $identificadorConsulta, "N", 4, true, true, "0"); // IDENTIFICADOR DEL ARCHIVO DE CONSULTA
			$encabezado .= $this.campo("00", $codigoInstitucion, "N", 4, true, true, "0"); // CODIGO DE INSTITUCION
			$encabezado .= $this.campo("01", $clave, "AN", 20, false, true, null);	// CLAVE DEL USUARIO
			$encabezado .= $this.campo("02", $pass, "AN", 20, false, true, null); 	// PASSWORD DE ACCESO
			$encabezado .= $this.campo("03", "ES", "A", 2, true, true, null); 	//CODIGO DE LENGUAJE
			$encabezado .= $this.campo("04", "MEX", "A", 3, true, true, null); 	//CODIGO DE PAIS
			$encabezado .= $this.campo("05", "ES", "A", 2, true, true, null); 	//IDIOMA
			$encabezado .= $this.campo("06", $this->numRegistros, "N", 4, false, false, "0"); //NUMERO DE REGISTROS PARA CONSULTA
			$encabezado .= $this.campo("07", "5", "AN", 1, true, true, "0"); //VERSION DEL ARCHIVO DE RESPUESTA
			$encabezado .="\n"; // Salto de linea obligatorio
		} catch(\Exception $e) { 
			Log::info($e);
	  		$this->idError = -1;
			$this->descError = "Error al generar el segmento HD para la consulta PM.";
			return "";
		}

		return $encabezado;
	}
	

	/*
	*genera_EM
	*/
	private function genera_EM($solicitantes) {
		$identificador = 0;
		$value = "";
		$datos = "";
		
		try {
			$res = $this->getClientesDatos($solicitantes); 

			while ($res) {
				$identificador += 1;
				$value = $identificador;
				$datos .= $this->campo("EM", $value, "AN", 20, false, true, ""); // IDENTIFICADOR DE LA CONSULTA

				$value = "001"; // 501: Informe Buro | 002: Monitor | 004: Reporte Califica | 005: Reporte Consolidado | 006: Monitor Consolidado | 007: Informe Buro con Historia de Consultas
				$datos .= $this->campo("00", $value, "AN", 3, true, true, "0"); // CLAVE DEL PRODUCTO REQUERIDO
				
				$value = $res->rfc;
				$datos .= $this->campo("01", $value, "AN", $value.length(), false, true, null); // RFC DEL CLIENTE
				
				
				$value = $res->nombre1;
				$datos .= $this->campo("02", $value, "AN", 75, false, true, null); // NOMBRE O RAZON SOCIAL DEL CLIENTE
				
				$value = $res->nombre2;
				$datos .= $this->campo("03", $value, "AN", 75, false, false, null); // SEGUNDO NOMBRE
				
				$value = $res->tipo_persona;
				if ("M" != $value) {
					$value = $res->ap_paterno;
					$datos .= $this->campo("04", $value, "AN", 25, false, true, null); // APELLIDO PATERNO
					
					$value = $res->ap_materno;
					$datos .= $this->campo("05", $value, "AN", 25, false, true, null); // APELLIDO MATERNO
				}
				
				$value = $res->domicilio1;
				$datos .= $this->campo("06", $value, "AN", 40, false, true, null); // PRIMERA LINEA DE DIRECCION
				
				$value = $res->domicilio2;
				$datos .= $this->campo("07", $value, "AN", 40, false, false, null); // SEGUNDA LINEA DE DIRECCION
				
				$value = $res->codigo_postal;
				$datos .= $this->campo("08", $value, "AN", 10, false, true, null); // CODIGO POSTAL
				
				$value = $res->colonia;
				$datos .= $this->campo("09", $value, "AN", 60, false, false, null); // COLONIA O POBLACION
				
				$value = $res->ciudad;
				$datos .= $this->campo("10", $value, "AN", 40, false, true, null); // CIUDAD
				
				$value = $res->estado;
				$datos .= $this->campo("11", $value, "AN", 40, false, true, null); // NOMBRE DEL ESTADO
				
				$value = $res->pais;
				$datos .= $this->campo("12", $value, "AN", 2, true, true, null); // PAIS DE ORIGEN DEL DOMICILIO
				
				$value = "S"; // S: Se cuenta con la firma aut�grafa de autorizaci�n del Cliente. | N: No se cuenta con firma aut�grafa
				$datos .= $this->campo("13", $value, "AN", 1, true, false, null); // FIRMA DE AUTORIZACION DEL CLIENTE
				
				$value = "R"; // R: Obtener informaci�n de compa��a con mayor porcentaje de similitud | N: No se requiere Informe Bur� del expediente del Cliente con ambig�edad
				$datos .= $this->campo("14", $value, "AN", 1, true, false, null); // AMBIGUEDAD
				
				$value = "S"; // S: Incluir variables CNBV | N: No se requiere incluir variables CNBV
				$datos .= $this->campo("15", $value, "A", 1, true, true, null); // INDICADOR DE VARIABLES
				
				$value = $res->id;
				$datos .= $this->campo("17", $value, "AN", 25, false, false, null); // REFERENCIA CREDITICIA
				
				// 1: PM | 2: PFAE | 3: Fondo o Fideicomiso | 4: Gobierno
				$value = (("F" == $res->tipo_persona) ? "2" : "1");
				$datos .= $this->campo("19", $value, "N", 1, true, false, null); // TIPO DE CLIENTE

				$datos .= "\n"; // Salto de linea obligatorio
			}
			
			if ("" == trim($datos)) {
				return "ERROR";
			}
		} catch (\Exception $e) {
			$e.printStackTrace();
			$this->idError = -1;
			$this->descError = "Error al generar el(los) segmento(s) EM para la consulta PM.";
			return "";
		}

		return $datos;
	}

	/*
	*genera_CI
	*/
	private  function  genera_CI($identificadorConsulta) {
		$segmentoCI = "";
		
		$segmentoCI = $this->campo("CI", $identificadorConsulta, "N", 4, true, true, "0") . "\n";
		//System.out.println("Segmento CI: " + segmentoCI);
		
		return $segmentoCI;
	}

	// ----------------------------------------------------------------------------------
	// RESPUESTA 
	// ----------------------------------------------------------------------------------
	
	/*
	*procesa_HD
	*/
	private function procesa_HD($value, $sfolio) {
		$query = "INSERT INTO buro_consultapm_hd (id_segmento, id_consulta, clave_retorno, id_transaccion, fecha_consulta, folio_consulta) VALUES (";
		
		try {
			$this->segmentos = $this->getSubsegmentos($value);
			if (empty($this->segmentos)) {
				$this->idError = -1;
				$this->descError = "Error al interpretar el segmento HD de la respuesta de buro.";
				throw new Exception("El segmento no tiene etiquetas.");
			}
			
			// id segmento
			if (array_key_exists("HD",$this->segmentos) &&  "" != $this->segmentos['HD']) {
				$this->idSegmentoHD = $this->segmentos["HD"];
				$query .= $this->segmentos["HD"];
			} else {
				$this->idSegmentoHD = -1;
				$query .= "-1";
			}
			
			// id consulta
			if (array_key_exists("00", $this->segmentos) &&  "" != $this->segmentos["00"]) {
				$query .= ", " . $this->segmentos["00"];
			} else {
				$query .= ", 0";
			}
			
			// clave retorno
			if (array_key_exists("01" , $this->segmentos) &&  "" != $this->segmentos["01"]) {
				$query .= ", " . $this->segmentos["01"];
			} else {
				$query .= ", 0";
			}
			
			// transaccion
			if (array_key_exists("02", $this->segmentos) &&  "" != $this->segmentos["02"]) {
				$query .= ", " . $this->segmentos["02"];
			} else {
				$query .= ", 0";
			}
			
			// fecha
			if (array_key_exists("03" , $this->segmentos) &&  "" != $this->segmentos["03"]) {
				$query .= ", '" . $this->convierteAFecha($this->segmentos["03"]) . "'";
			} else {
				$query .= "NOW()";
			}
			
			$query .= ", " . $sfolio . ");";
		} catch (\Exception $e) {
			$this->idError = -6;
			$this->descError = "Error al interpretar el segmento HD de la respuesta de buro - " . $e->getMessage() . " [" . $value . "]";
			return;
		}
		
		try{
			Log::info($query);
			$st = DB::insert($query)	;
		} catch(\Exception $e) {
			$this->idError = -7;
			$this->descError = "Error al guardar segmento HD de la respuesta de buro - " . $e->getMessage();
            return;
		}
	}

	/*
	*procesa_EM
	*/

	private function procesa_EM($value, $sfolio) {
		$query = "INSERT INTO buro_consultapm_em (id_segmento, tipo_persona, rfc, curp, nombre, segundo_nombre, "
				. "apellido_paterno, apellido_materno, direccion1, direccion2, colonia, municipio, ciudad, estado, codigo_postal, "
				. "pais, telefono, extension_tel, fax, nacionalidad, calificacion_cartera, codigo_scian1, codigo_scian2, codigo_scian3, "
				. "uso_futuro, clave_prevencion, num_consultas_entidades_financieras_3_meses, num_consultas_entidades_financieras_12_meses, "
				. "num_consultas_entidades_financieras_24_meses, num_consultas_entidades_financieras_24mas_meses, "
				. "num_consultas_empresas_comerciales_3_meses, num_consultas_empresas_comerciales_12_meses, "
				. "num_consultas_empresas_comerciales_24_meses, num_consultas_empresas_comerciales_24mas_meses, "
				. "indicador_informacion_adicional, clave_prevencion_persona_relacionada, clave_prevencion_impugnada, "
				. "clave_prevencion_impugnada_persona_relacionada, id_segmento_hd, folio_consulta) VALUES (";

		try {
			$this->segmentos = $this->getSubsegmentos($value);
			if (empty($this->segmentos)) {
				$this->idError = -1;
				$this->descError = "Error al interpretar el segmento EM de la respuesta de buro.";
				throw new Exception("El segmento no tiene etiquetas.");
			}
			
			// id segmento
			if (array_key_exists( "EM" , $this->segmentos) &&  "" !=  $this->segmentos["EM"]) {
				$query .= $this->segmentos["EM"];
			} else {
				$query .= "-1";
			}
			
			// id consulta
			if (array_key_exists("00", $this->segmentos) &&  ""  != $this->segmentos["00"]) {
				$query .= ", " . $this->segmentos["00"];
			} else {
				$query .= ", 0";
			}
			
			if (array_key_exists($this->segmentos["01"] &&  "" != $this->segmentos["01"])) {
				$query .= ", '" . $this->segmentos["01"] . "'";
			} else {
				$query .= ", ''";
			}
			
			if (array_key_exists("02", $this->segmentos) &&  "" !=  $this->segmentos["02"]) {
				$query .= ", '" . $this->segmentos["02"] . "'";
			} else {
				$query .= ", ''";
			}
			
			if (array_key_exists($this->segmentos["03"] &&  ""  != $this->segmentos["03"])) {
				$query .= ", '" . $this->segmentos["03"] . "'";
			} else {
				$query .= ", ''";
			}
			
			if (array_key_exists("04", $this->segmentos) &&  ""  != $this->segmentos["04"]) {
				$query .= ", '" . $this->segmentos["04"] . "'";
			} else {
				$query .= ", ''";
			}
			
			if (array_key_exists("05", $this->segmentos) &&  "" != $this->segmentos["05"]) {
				$query .= ", '" . $this->segmentos["05"] . "'";
			} else {
				$query .= ", ''";
			}
			
			if (array_key_exists($this->segmentos["06"] &&  "" != $this->segmentos["06"])) {
				$query .= ", '" . $this->segmentos["06"] . "'";
			} else {
				$query .= ", ''";
			}
			
			if (array_key_exists("07", $this->segmentos) &&  ""  != $this->segmentos["07"]) {
				$query .= ", '" . $this->segmentos["07"] . "'";
			} else {
				$query .= ", ''";
			}
			
			if (array_key_exists("08" , $this->segmentos) &&  ""  != $this->segmentos["08"]) {
				$query .= ", '" . $this->segmentos["08"] . "'";
			} else {
				$query .= ", ''";
			}
			
			if (array_key_exists("09", $this->segmentos) &&  "" != $this->segmentos["09"]) {
				$query .= ", '" . $this->segmentos["09"] . "'";
			} else {
				$query .= ", ''";
			}
			
			if (array_key_exists("10" , $this->segmentos) &&  "" !=  $this->segmentos["10"]) {
				$query .= ", '" . $this->segmentos["10"] . "'";
			} else {
				$query .= ", ''";
			}
			
			if (array_key_exists("11", $this->segmentos) &&  "" != $this.segmentos["11"]) {
				$query .= ", '" . $this->segmentos["11"] . "'";
			} else {
				$query .= ", ''";
			}
			
			if (array_key_exists("12", $this->segmentos) &&  "" != $this->segmentos["12"]) {
				$query .= ", '" . $this->segmentos["12"] . "'";
			} else {
				$query .= ", ''";
			}
			
			if (array_key_exists("13" , $this->segmentos) &&  "" != $this->segmentos["13"]) {
				$query .= ", '" . $this->segmentos["13"] . "'";
			} else {
				$query .= ", ''";
			}
			
			if (array_key_exists("14" , $this->segmentos) &&  "" != $this->segmentos["14"]) {
				$query .= ", '" . $this->segmentos["14"] . "'";
			} else {
				$query .= ", ''";
			}
			
			if (array_key_exists("15" , $this->segmentos) &&  "" != $this->segmentos["15"]) {
				$query .= ", '" . $this->segmentos["15"] . "'";
			} else {
				$query .= ", ''";
			}
			
			if (array_key_exists("16" , $this->segmentos) &&  "" != $this->segmentos["16"]) {
				$query .= ", '" . $this->segmentos["16"] . "'";
			} else {
				$query .= ", ''";
			}
			
			if (array_key_exists("17" , $this->segmentos) &&  "" != $this->segmentos["17"]) {
				$query .= ", '" . $this->segmentos["17"] . "'";
			} else {
				$query .= ", ''";
			}
			
			if (array_key_exists("18" , $this->segmentos) &&  "" != $this->segmentos["18"]) {
				$query .= ", '" . $this->segmentos["18"] . "'";
			} else {
				$query .= ", ''";
			}
			
			if (array_key_exists("19" , $this->segmentos) &&  "" != $this->segmentos["19"]) {
				$query .= ", '" . $this->segmentos["19"] . "'";
			} else {
				$query .= ", ''";
			}
			
			if (array_key_exists("20" , $this->segmentos) &&  "" != $this->segmentos["20"]) {
				$query .= ", '" . $this->segmentos["20"] . "'";
			} else {
				$query .= ", ''";
			}
			
			if (array_key_exists("21" , $this->segmentos) &&  "" !=  $this->segmentos["21"]) {
				$query .= ", '" . $this->segmentos["21"] . "'";
			} else {
				$query .= ", ''";
			}
			
			if (array_key_exists("22" , $this->segmentos) &&  "" != $this->segmentos["22"]) {
				$query .= ", '" . $this->segmentos["22"] . "'";
			} else {
				$query .= ", ''";
			}
			
			if (array_key_exists("23" , $this->segmentos) &&  "" != $this->segmentos["23"]) {
				$query .= ", " . $this->segmentos["23"];
			} else {
				$query .= ", 0";
			}
			
			if (array_key_exists("24" , $this->segmentos) &&  "" != $this->segmentos["24"]) {
				$query .= ", '" . $this->segmentos["24"] . "'";
			} else {
				$query .= ", ''";
			}
			
			if (array_key_exists("25" , $this->segmentos) &&  "" != $this->segmentos["25"]) {
				$query .= ", " . $this->segmentos["25"];
			} else {
				$query .= ", 0";
			}
			
			if (array_key_exists("26" , $this->segmentos) &&  "" != $this->segmentos["26"]) {
				$query .= ", " . $this->segmentos["26"];
			} else {
				$query .= ", 0";
			}
			
			if (array_key_exists("27" , $this->segmentos) &&  "" != $this->segmentos["27"]) {
				$query .= ", " . $this->segmentos["27"];
			} else {
				$query .= ", 0";
			}
			
			if (array_key_exists("28" , $this->segmentos) &&  "" != ($this->segmentos["28"])) {
				$query .= ", " . $this->segmentos["28"];
			} else {
				$query .= ", 0";
			}
			
			if (array_key_exists("29" , $this->segmentos) &&  "" != $this->segmentos["29"]) {
				$query .= ", " . $this->segmentos["29"];
			} else {
				$query .= ", 0";
			}
			
			if (array_key_exists("30" , $this->segmentos) &&  "" != $this->segmentos["30"]) {
				$query .= ", " . $this->segmentos["30"];
			} else {
				$query .= ", 0";
			}
			
			if (array_key_exists("31" , $this->segmentos) &&  "" != $this->segmentos["31"]) {
				$query .= ", " . $this->segmentos["31"];
			} else {
				$query .= ", 0";
			}
			
			if (array_key_exists("32" , $this->segmentos) &&  "" != $this->segmentos["32"]) {
				$query .= ", " . $this->segmentos["32"];
			} else {
				$query .= ", 0";
			}
			
			if (array_key_exists("33" , $this->segmentos) &&  "" != $this->segmentos["33"]) {
				$query .= ", '" . $this->segmentos["33"] . "'";
			} else {				
				$query .= ", ''";
			}
			
			if (array_key_exists("34" , $this->segmentos) &&  "" != $this->segmentos["34"]) {
				$query .= ", '" . $this->segmentos["34"] . "'";
			} else {
				$query .= ", ''";
			}
			
			if (array_key_exists("35" , $this->segmentos) &&  "" != $this->segmentos["35"]) {
				$$query .= ", '" . $this->segmentos["35"] . "'";
			} else {
				$query .= ", ''";
			}
			
			if (array_key_exists("36" , $this->segmentos) &&  "" != $this->segmentos["36"]) {
				$query .= ", '" . $this->segmentos["36"] . "'";
			} else {
				$query .= ", ''";
			}
			
			$query .= ", " . $this->idSegmentoHD . ", " . $sfolio .");";
		} catch (\Exception $e) {
			$this->idError = -6;
			$this->descError = "Error al interpretar el segmento EM de la respuesta de buro - " . $e->getMessage() . " [" . $value . "]";
			return;
		}
		
		try{
			Log::info(query);
			$st = DB::insert($query);			
		} catch(\Exception $e) {
			$this->idError = -7;
			$this->descError = "Error al guardar segmento EM de la respuesta de buro - " . $e->getMessage();
            return;
		}
	}

	/*
	*procesa_EM_OLD
	*/
	private function procesa_EM_OLD($sfolio, $segmento) {
		$this->segmentos = $this->getSubsegmentos($segmento);
		$insert = "";
		
		// RECUPERAMOS PERSONA
		// -----------------------------------------------------------------------------------------------------
		try{
			$insert = $this->insertQueryPersona($this->segmentos, $sfolio);
			Log::info(insert);
			$st = DB::insert($insert);			
		} catch(\Exception $e) {
			Log::info($e);
		}
		
		// RECUPERAMOS DOMICILIO
		// -----------------------------------------------------------------------------------------------------
		
		try{
			$insert = $this->insertQueryDomicilio($this->segmentos, $sfolio);
			Log::info(insert);
			$st = DB::insert($insert);			
		} catch(\Exception $e) {
			Log::info($e);
		}
	}

	/*
	*procesa_HC
	*/
	private function procesa_HC($value, $sfolio) {
		$query = "INSERT INTO buro_consultapm_hc (id_segmento, fecha, codigo, usuario_reporta, descripcion, id_segmento_hd, folio_consulta) VALUES (";

		try {
			$this->segmentos = $this->getSubsegmentos($value);
			if (empty($this->segmentos)) {
				$this->idError = -1;
				$this->descError = "Error al interpretar el segmento HC de la respuesta de buro.";
				throw new Exception("El segmento no tiene etiquetas.");
			}
			
			if (array_key_exists("HC" , $this->segmentos) &&  "" != $this->segmentos["HC"]) {
				$query .= $this->segmentos["HC"];
			} else {
				$query .= "-1";
			}
			
			if (array_key_exists("00" , $this->segmentos) && ! "" !=  $this->segmentos["00"]) {
				$query .= ", '" . $this->convierteAFecha($this->segmentos["00"]) . "'";
			} else {
				$query .= ", NOW()";
			}
			
			if (array_key_exists("01" , $this->segmentos) &&  "" != $this->segmentos["01"]) {
				$query .= ", " . $this->segmentos["01"];
			} else {
				$query .= ", 0";
			}
			
			if (array_key_exists("02" , $this->segmentos) &&  "" != $this->segmentos["02"]) {
				$query .= ", '" . $this->segmentos["02"] . "'";
			} else {
				$query .= ", ''";
			}
			
			if (array_key_exists("03" , $this->segmentos) &&  "" !=  $this->segmentos["03"]) {
				$query .= ", '" . $this->segmentos["03"] . "'";
			} else {
				$query .= ", ''";
			}
			
			$query .= ", " . $this->idSegmentoHD . ", " . $sfolio . ");";
		} catch (\Exception $e) {
			$this->idError = -6;
			$this->descError = "Error al interpretar el segmento HC de la respuesta de buro - " . $e->getMessage() . " [" . $value . "]";
			return;
		}
		
		try{
			Log::info($query);
			$st = DB::insert($query);			
		} catch(\Exception $e) {
			$this->idError = -7;
			$this->descError = "Error al guardar segmento HC de la respuesta de buro - " . $e->getMessage();
            return;
		}
	}

	/*
	*procesa_HR
	*/
	private function procesa_HR($value, $sfolio) {
		$query = "INSERT INTO buro_consultapm_hr (id_segmento, fecha, codigo, tipo_usuario_reporta, descripcion, id_segmento_hd, folio_consulta) VALUES (";

		try {
			$this->segmentos = $this->getSubsegmentos($value);
			if (empty($this->segmentos)) {
				$this->idError = -1;
				$this->descError = "Error al interpretar el segmento HR de la respuesta de buro.";
				throw new Exception("El segmento no tiene etiquetas.");
			}
			
			if (array_key_exists("HR" , $this->segmentos) &&  "" != $this->segmentos["HR"]) {
				$query .= $this->segmentos["HR"];
			} else {
				$query .= "-1";
			}
			
			if (array_key_exists("00" , $this->segmentos) &&  "" != $this->segmentos["00"]) {
				$query .= ", '" . $this->convierteAFecha($this->segmentos["00"]) . "'";
			} else {
				$query .= ", NOW()";
			}
			
			if (array_key_exists("01" , $this->segmentos) &&  "" != $this->segmentos["01"]) {
				$query .= ", " . $this->segmentos["01"];
			} else {
				$query .= ", 0";
			}
			
			if (array_key_exists("02" , $this->segmentos) &&  "" != $this->segmentos["02"]) {
				$query .= ", '" . $this->segmentos["02"] . "'";
			} else {
				$query .= ", ''";
			}
			
			if (array_key_exists("03" , $this->segmentos) &&  "" != $this->segmentos["03"]) {
				$query .= ", '" . $this->segmentos["03"] . "'";
			} else {
				$query .= ", ''";
			}
			
			$query .= ", " . $this->idSegmentoHD . ", " . $sfolio . ");";
		} catch (\Exception $e) {
			$this->idError = -6;
			$this->descError = "Error al interpretar el segmento HR de la respuesta de buro - " . $e->getMessage() . " [" . $value . "]";
			return;
		}
		
		try{
			Log::info($query);
			$st =DB::insert($query);			
		} catch(\Exception $e) {
			$this->idError = -7;
			$this->descError = "Error al guardar segmento HR de la respuesta de buro - " . $e->getMessage();
            return;
		}
	}

	/*
	*procesa_DC
	*/
	private function procesa_DC($value, $sfolio) {
		$query = "INSERT INTO buro_consultapm_dc (id_segmento, rfc, declarativa1, declarativa2, declarativa3, declarativa4, declarativa5, " 
				. "declarativa6, declarativa7, declarativa8, declarativa10, declarativa11, declarativa12, declarativa13, declarativa14, "
				. "declarativa15, declarativa16, declarativa17, declarativa18, declarativa19, longitud_declarativa21, declarativa21, "
				. "fecha_declarativa, tipo_declarativa, clasif_tipo_otorgante, numero_contrato, tipo_credito, id_segmento_hd, folio_consulta) VALUES (";

		try {
			$this->segmentos = $this->getSubsegmentos($value);
			if (empty($this->segmentos)) {
				$this->idError = -1;
				$this->descError = "Error al interpretar el segmento DC de la respuesta de buro.";
				throw new Exception("El segmento no tiene etiquetas.");
			}
			
			if (array_key_exists("DC" , $this->segmentos) &&  "" != $this->segmentos["DC"]) {
				$query .= $this->segmentos["DC"];
			} else {
				$query .= "-1";
			}
			
			if (array_key_exists("00" , $this->segmentos) && "" != $this->segmentos["00"]) {
				$query .= ", 'STR" . $this->segmentos["00"] . "'";
			} else {
				$query .= ", ''";
			}
			
			if (array_key_exists("01" , $this->segmentos) &&  "" !=  $this->segmentos["01"]) {
				$query .= ", '" . $this->segmentos["01"] . "'";
			} else {
				$query .= ", ''";
			}
			
			if (array_key_exists("02" , $this->segmentos) &&  "" != $this->segmentos["02"]) {
				$query .= ", '" . $this->segmentos["02"] . "'";
			} else {
				$query += ", ''";
			}
			
			if (array_key_exists("03" , $this->segmentos) &&  "" != $this->segmentos["03"]) {
				$query .= ", '" . $this->segmentos["03"] + "'";
			} else {
				$query .= ", ''";
			}
			
			if (array_key_exists("04" , $this->segmentos) &&  "" != ($this->segmentos["04"])) {
				$query .= ", '" . $this->segmentos["04"] . "'";
			} else {
				$query .= ", ''";
			}
			
			if (array_key_exists("05" , $this->segmentos) &&  "" != $this->segmentos["05"]) {
				$query .= ", '" . $this->segmentos["05"] . "'";
			} else {
				$query += ", ''";
			}
			
			if (array_key_exists("06" , $this->segmentos) &&  "" != $this->segmentos["06"]) {
				$query .= ", '" . $this->segmentos["06"] . "'";
			} else {
				$query .= ", ''";
			}
			
			if (array_key_exists("07" , $this->segmentos) &&  "" != $this->segmentos["07"]) {
				$query .= ", '" . $this->segmentos["07"] . "'";
			} else {
				$query .= ", ''";
			}
			
			if (array_key_exists("08" , $this->segmentos) &&  "" !=  $this->segmentos["08"]) {
				$query .= ", '" . $this->segmentos["08"] . "'";
			} else {
				$query .= ", ''";
			}
			
			if (array_key_exists("09" , $this->segmentos) &&  "" !=  $this->segmentos["09"]) {
				$query .= ", '" . $this->segmentos["09"] . "'";
			} else {
				$query .= ", ''";
			}
			
			if (array_key_exists("10" , $this->segmentos) &&  "" != $this->segmentos["10"]) {
				$query .= ", '" . $this->segmentos["10"] . "'";
			} else {
				$query .= ", ''";
			}
			
			if (array_key_exists("11" , $this->segmentos) &&  "" != $this->segmentos["11"]) {
				$query .= ", '" . $this->segmentos["11"] . "'";
			} else {
				$query .= ", ''";
			}
			
			if (array_key_exists("12" , $this->segmentos) &&  "" !=  $this->segmentos["12"]) {
				$query .= ", '" . $this->segmentos["12"] . "'";
			} else {
				$query .= ", ''";
			}
			
			if (array_key_exists("13" , $this->segmentos) &&  "" !=  $this->segmentos["13"]) {
				$query .= ", '" . $this->segmentos["13"] . "'";
			} else {
				$query .= ", ''";
			}
			
			if (array_key_exists("14" , $this->segmentos) &&  "" != $this->segmentos["14"]) {
				$query .= ", '" . $this->segmentos["14"] . "'";
			} else {
				$query .= ", ''";
			}
			
			if (array_key_exists("15" , $this->segmentos) &&  "" != $this->segmentos["15"]) {
				$query .= ", '" . $this->segmentos["15"] . "'";
			} else {
				$query .= ", ''";
			}
			
			if (array_key_exists("16" , $this->segmentos) &&  "" != $this->segmentos["16"]) {
				$query .= ", '" . $this->segmentos["16"] . "'";
			} else {
				$query .= ", ''";
			}
			
			if (array_key_exists("17" , $this->segmentos) &&  "" != $this->segmentos["17"]) {
				$query .= ", '" . $this->segmentos["17"] . "'";
			} else {
				$query .= ", ''";
			}
			
			if (array_key_exists("18" , $this->segmentos) &&  "" !=  $this->segmentos["18"]) {
				$query .= ", '" . $this->segmentos["18"] . "'";
			} else {
				$query .= ", ''";
			}
			
			if (array_key_exists("19" , $this->segmentos) &&  ""  !=  $this->segmentos["19"]) {
				$query .= ", '" . $this->segmentos["19"] . "'";
			} else {
				$query .= ", ''";
			}
			
			if (array_key_exists("20" , $this->segmentos) &&  "" !=  $this->segmentos["20"]) {
				$query .= ", " . $this->segmentos["20"];
			} else {
				$query .= ", 0";
			}
			
			if (array_key_exists("21" , $this->segmentos) &&  "" != $this->segmentos["21"]) {
				$query .= ", '" . $this->segmentos["21"] . "'";
			} else {
				$query .= ", ''";
			}
			
			if (array_key_exists("22" , $this->segmentos) &&  "" != ($this->segmentos["22"])) {
				$query .= ", '" . $this->convierteAFecha($this->segmentos["22"]) . "'";
			} else {
				$query .= ", NOW()";
			}
			
			if (array_key_exists("23" , $this->segmentos) &&  "" != $this->segmentos["23"]) {
				$query .= ", " . $this->segmentos["23"];
			} else {
				$query .= ", 0";
			}
			
			if (array_key_exists("24" , $this->segmentos) &&  "" !=  $this->segmentos["24"]) {
				$query .= ", " . $this->segmentos["24"];
			} else {
				$query .= ", 0";
			}
			
			if (array_key_exists("25" , $this->segmentos) && "" != ($this->segmentos["25"])) {
				$query .= ", '" . $this->segmentos["25"] . "'";
			} else {
				$query .= ", ''";
			}
			
			if (array_key_exists("26" , $this->segmentos) &&  "" != $this->segmentos["26"]) {
				$query .= ", " . $this->segmentos["26"];
			} else {
				$query .= ", 0";
			}
			
			$query .= ", " . $this->idSegmentoHD . ", " . $sfolio . ");";
		} catch (\Exception $e) {
			$this->idError = -6;
			$this->descError = "Error al interpretar el segmento DC de la respuesta de buro - " . $e->getMessage() . " [" . $value . "]";
			return;
		}
		
		try{
			Log::info($query);
			$st = DB::insert($query) ;			
		} catch(\Exception $e) {
			$this->idError = -7;
			$this->descError = "Error al guardar segmento DC de la respuesta de buro - " . $e->getMessage();
            return;
		}
	}

	/*
	*procesa_AC
	*/
	private function procesa_AC($value, $sfolio) {
		$query = "INSERT INTO buro_consultapm_ac (id_segmento, tipo_persona_aval, rfc, curp, nombre_aval, segundo_nombre, " 
				. "apellido_paterno, apellido_materno, direccion1, direccion2, colonia, municipio, ciudad, estado, codigo_postal, "
				. "pais, telefono, extension_tel, fax, tipo_persona, porcentaje_accionistas, cantidad_avalada, id_segmento_hd, folio_consulta) VALUES (";

		try {
			$this->segmentos = $this->getSubsegmentos($value);
			if (empty($this->segmentos)) {
				$this->idError = -1;
				$this->descError = "Error al interpretar el segmento AC de la respuesta de buro.";
				throw new Exception("El segmento no tiene etiquetas.");
			}
			
			if (array_key_exists("AC" , $this->segmentos) &&  "" != $this->segmentos["AC"]) {
				$query .= $this->segmentos["AC"];
			} else {
				$query .= "-1";
			}
			
			if (array_key_exists("00" , $this->segmentos) &&  "" != $this->segmentos["00"]) {
				$query .= ", '" . $this->segmentos["00"] . "'";
			} else {
				$query .= ", ''";
			}
			
			if (array_key_exists("01" , $this->segmentos) &&  "" != $this->segmentos["01"]) {
				$query .= ", '" . $this->segmentos["01"] . "'";
			} else {
				$query .= ", ''";
			}
			
			if (array_key_exists("02" , $this->segmentos) &&  "" != $this->segmentos["02"]) {
				$query .= ", '" . $this->segmentos["02"] . "'";
			} else {
				$query .= ", ''";
			}
			
			if (array_key_exists("03" , $this->segmentos) &&  "" != $this->segmentos["03"]) {
				$query .= ", '" . $this->segmentos["03"] . "'";
			} else {
				$query .= ", ''";
			}
			
			if (array_key_exists("04" , $this->segmentos) && "" != $this->segmentos["04"]) {
				$query .= ", '" . $this->segmentos["04"] . "'";
			} else {
				$query .= ", ''";
			}
			
			if (array_key_exists("05" , $this->segmentos) &&  "" != $this->segmentos["05"]) {
				$query .= ", '" . $this->segmentos["05"] . "'";
			} else {
				$query .= ", ''";
			}
			
			if (array_key_exists("06" , $this->segmentos) &&  "" != $this->segmentos["06"]) {
				$query .= ", '" . $this->segmentos["06"] . "'";
			} else {
				$query .= ", ''";
			}
			
			if (array_key_exists("07" , $this->segmentos) && "" != $this->segmentos["07"]) {
				$query .= ", '" . $this->segmentos["07"] . "'";
			} else {
				$query .= ", ''";
			}
			
			if (array_key_exists("08" , $this->segmentos) &&  "" != $this->segmentos["08"]) {
				$query .= ", '" . $this->segmentos["08"] . "'";
			} else {
				$query .= ", ''";
			}
			
			if (array_key_exists("09" , $this->segmentos) &&  "" != $this->segmentos["09"]) {
				$query .= ", '" . $this->segmentos["09"] . "'";
			} else {
				$query .= ", ''";
			}
			
			if (array_key_exists("10" , $this->segmentos) &&  "" != $this->segmentos["10"]) {
				$query .= ", '" . $this->segmentos["10"] . "'";
			} else {
				$query .= ", ''";
			}
			
			if (array_key_exists("11" , $this->segmentos) &&  "" !=  $this->segmentos["11"]) {
				$query .= ", '" . $this->segmentos["11"] . "'";
			} else {
				$query .= ", ''";
			}
			
			if (array_key_exists("12" , $this->segmentos) &&  "" != $this->segmentos["12"]) {
				$query .= ", '" . $this->segmentos["12"] . "'";
			} else {
				$query .= ", ''";
			}
			
			if (array_key_exists("13" , $this->segmentos) && ! "" !=  $this->segmentos["13"]) {
				$query .= ", '" . $this->segmentos["13"] . "'";
			} else {
				$query .= ", ''";
			}
			
			if (array_key_exists("14" , $this->segmentos) &&  "" !=  $this->segmentos["14"]) {
				$query .= ", '" . $this->segmentos["14"] . "'";
			} else {
				$query .= ", ''";
			}
			
			if (array_key_exists("15" , $this->segmentos) &&  "" != $this->segmentos["15"]) {
				$query .= ", '" . $this->segmentos["15"] . "'";
			} else {
				$query .= ", ''";
			}
			
			if (array_key_exists("16" , $this->segmentos) &&  "" !=  $this->segmentos["16"]) {
				$query .= ", '" . $this->segmentos["16"] . "'";
			} else {
				$query .= ", ''";
			}
			
			if (array_key_exists("17" , $this->segmentos) &&  "" !=  $this->segmentos["17"]) {
				$query .= ", '" . $this->segmentos["17"] . "'";
			} else {
				$query .= ", ''";
			}
			
			if (array_key_exists("18" , $this->segmentos) &&  "" != $this->segmentos["18"]) {
				$query .= ", " . $this->segmentos["18"];
			} else {
				$query .= ", 0";
			}
			
			if (array_key_exists("19" , $this->segmentos) &&  "" != $this->segmentos["19"]) {
				$query .= ", " . $this->segmentos["19"];
			} else {
				$query .= ", 0";
			}
			
			if (array_key_exists("20" , $this->segmentos) && "" !=  $this->segmentos["20"]) {
				$query .= ", " . $this->segmentos["20"];
			} else {
				$query .= ", 0";
			}
			
			$query .= ", " . $this->idSegmentoHD . ", " . $sfolio . ");";
		} catch (\Exception $e) {
			$this->idError = -6;
			$this->descError = "Error al interpretar el segmento AC de la respuesta de buro - " . $e->getMessage() . " [" . $value . "]";
			return;
		}
		
		try{
			Log::info($query);
			$st = DB::insert($query);			
		} catch(\Exception $e) {
			$this->idError = -7;
			$this->descError = "Error al guardar segmento AC de la respuesta de buro - " . $e->getMessage();
            return;
		}
	}

	/*
	*procesa_CR
	*/
	private function procesa_CR($value, $sfolio) {
		$query = "INSERT INTO buro_consultapm_cr (id_segmento, rfc, numero_contrato, tipo_usuario, saldo_inicial, moneda, " 
				. "fecha_apertura_credito, plazo, tipo_cambio, clave_observacion, tipo_credito, saldo_vigente, saldo_vencido_1_29, "
				. "saldo_vencido_30_59, saldo_vencido_60_89, saldo_vencido_90_119, saldo_vencido_120_179, saldo_vencido_180mas, "
				. "ultimo_periodo_actualizado, fecha_cierre, pago_cierre, quita, dacion_pago, quebranto, historico_pagos, atraso_mayor, "
				. "registro_impugnado, historia_dias, numero_pagos, frecuencia_pagos, monto_pagar, fecha_ultimo_pago, fecha_reestructura, "
				. "fecha_primer_incumplimiento, saldo_insoluto_principal, credito_maximo_utilizado, fecha_ingreso_cartera_vencida, id_segmento_hd, folio_consulta) VALUES (";
		
		try {
			$this->segmentos = $this->getSubsegmentos($value);
			if (empty($this->segmentos)) {
				$this->idError = -1;
				$this->descError = "Error al interpretar el segmento CR de la respuesta de buro.";
				throw new Exception("El segmento no tiene etiquetas.");
			}
			
			if (array_key_exists("CR" , $this->segmentos) &&  "" != $this->segmentos["CR"]) {
				$query .= $this->segmentos["CR"];
			} else {
				$query .= "-1";
			}
			
			if (array_key_exists("00" , $this->segmentos) &&  "" != $this->segmentos["00"]) {
				$query .= ", '" . $this->segmentos["00"] . "'";
			} else {
				$query .= ", ''";
			}
					
			if (array_key_exists("01" , $this->segmentos) && "" !=$this->segmentos["01"]) {
				$query .= ", '" . $this->segmentos["01"] . "'";
			} else {
				$query .= ", ''";
			}
			
			if (array_key_exists("02" , $this->segmentos) && "" != $this->segmentos["02"]) {
				$query .= ", '" . $this->segmentos["02"] . "'";
			} else {
				$query .= ", ''";
			}
			
			if (array_key_exists("03" , $this->segmentos) && "" !=  $this->segmentos["03"]) {
				$query .= ", " . $this->segmentos["03"];
			} else {
				$query .= ", 0";
			}
			
			if (array_key_exists("04" , $this->segmentos) &&  "" != $this->segmentos["04"]) {
				$query .= ", '" . $this->segmentos["04"] . "'";
			} else {
				$query .= ", ''";
			}
			
			if (array_key_exists("05" , $this->segmentos) && "" != $this->segmentos["05"]) {
				$query .= ", '" . $this->convierteAFecha($this->segmentos["05"]) . "'";
			} else {
				$query .= ", NOW()";
			}
			
			if (array_key_exists("06" , $this->segmentos) && "" != $this->segmentos["06"]) {
				$query .= ", " . $this->segmentos["06"];
			} else {
				$query .= ", 0";
			}
			
			if (array_key_exists("07" , $this->segmentos) && "" != $this->segmentos["07"]) {
				$query .= ", " . $this->segmentos["07"];
			} else {
				$query .= ", 0";
			}
			
			if (array_key_exists("08" , $this->segmentos) &&  "" != $this->segmentos["08"]) {
				$query .= ", '" . $this->segmentos["08"] . "'";
			} else {
				$query .= ", ''";
			}
			
			if (array_key_exists("09" , $this->segmentos) && "" != $this->segmentos["09"]) {
				$query .= ", " . $this->segmentos["09"];
			} else {
				$query .= ", 0";
			}
			
			if (array_key_exists("10" , $this->segmentos) && "" != $this->segmentos["10"]) {
				$query .= ", " . $this->segmentos["10"];
			} else {
				$query .= ", 0";
			}
			
			if (array_key_exists("11" , $this->segmentos) && "" != $this->segmentos["11"]) {
				$query .= ", " . $this->segmentos["11"];
			} else {
				$query .= ", 0";
			}
			
			if (array_key_exists("12" , $this->segmentos) &&  "" != $this->segmentos["12"]) {
				$query .= ", " . $this->segmentos["12"];
			} else {
				$query .= ", 0";
			}
			
			if (array_key_exists("13" , $this->segmentos) && "" != $this->segmentos["13"]) {
				$query .= ", " . $this->segmentos["13"];
			} else {
				$query .= ", 0";
			}
			
			if (array_key_exists("14" , $this->segmentos) && "" != $this->segmentos["14"]) {
				$query .= ", " . $this->segmentos["14"];
			} else {
				$query .= ", 0";
			}
			
			if (array_key_exists("15" , $this->segmentos) && "" != $this->segmentos["15"]) {
				$query .= ", " . $this->segmentos["15"];
			} else {
				$query .= ", 0";
			}
			
			if (array_key_exists("16" , $this->segmentos) && "" != $this->segmentos["16"]) {
				$query .= ", " . $this->segmentos["16"];
			} else {
				$query .= ", 0";
			}
			
			if (array_key_exists("17" , $this->segmentos) && "" != $this->segmentos["17"]) {
				$query .= ", '" . $this->segmentos["17"] . "'";
			} else {
				$query .= ", ''";
			}
			
			if (array_key_exists("18" , $this->segmentos) && "" != $this->segmentos["18"]) {
				$query .= ", '" . $this->convierteAFecha($this->segmentos["18"]) . "'";
			} else {
				$query .= ", now()";
			}
			
			if (array_key_exists("19" , $this->segmentos) && "" != $this->segmentos["19"]) {
				$query .= ", " . $this->segmentos["19"];
			} else {
				$query .= ", 0";
			}
			
			if (array_key_exists("20" , $this->segmentos) && "" != $this->segmentos["20"]) {
				$query .= ", " . $this->segmentos["20"];
			} else {
				$query .= ", 0";
			}
			
			if (array_key_exists("21" , $this->segmentos) &&  "" != $this->segmentos["21"]) {
				$query .= ", " . $this->segmentos["21"];
			} else {
				$query .= ", 0";
			}
			
			if (array_key_exists("22" , $this->segmentos) && "" != $this->segmentos["22"]) {
				$query .= ", " . $this->segmentos["22"];
			} else {
				$query .= ", 0";
			}
			
			if (array_key_exists("23" , $this->segmentos) && "" != $this->segmentos["23"]) {
				$query .= ", '" . $this->segmentos["23"] . "'";
			} else {
				$query .= ", ''";
			}
			
			if (array_key_exists("24" , $this->segmentos) && "" != $this->segmentos["24"]) {
				$query .= ", " . $this->segmentos["24"];
			} else {
				$query .= ", 0";
			}
			
			if (array_key_exists("25" , $this->segmentos) && "" != $this->segmentos["25"]) {
				$query .= ", '" . $this->segmentos["25"] . "'";
			} else {
				$query .= ", ''";
			}
			
			if (array_key_exists("26" , $this->segmentos) && "" != $this->segmentos["26"]) {
				$query .= ", '" . $this->segmentos["26"] . "'";
			} else {
				$query .= ", ''";
			}
			
			if (array_key_exists("27" , $this->segmentos) && "" != $this->segmentos["27"]) {
				$query .= ", " . $this->segmentos["27"];
			} else {
				$query .= ", 0";
			}
			
			if (array_key_exists("28" , $this->segmentos) && "" != $this->segmentos["28"]) {
				$query .= ", " . $this->segmentos["28"];
			} else {
				$query .= ", 0";
			}
			
			if (array_key_exists("29" , $this->segmentos) && "" != $this->segmentos["29"]) {
				$query .= ", " . $this->segmentos["29"];
			} else {
				$query .= ", 0";
			}
			
			if (array_key_exists("30" , $this->segmentos) && "" != $this->segmentos["30"]) {
				$query .= ", '" . $this->convierteAFecha($this->segmentos["30"]) . "'";
			} else {
				$query .= ", now()";
			}
			
			if (array_key_exists("31" , $this->segmentos) && "" != $this->segmentos["31"]) {
				$query .= ", '" . $this->convierteAFecha($this->segmentos["31"]) . "'";
			} else {
				$query .= ", now()";
			}
			
			if (array_key_exists("32" , $this->segmentos) && "" != $this->segmentos["32"]) {
				$query .= ", '" . $this->convierteAFecha($this->segmentos["32"]) . "'";
			} else {
				$query .= ", now()";
			}
			
			if (array_key_exists("33" , $this->segmentos) && "" != $this->segmentos["33"]) {
				$query .= ", " . $this->segmentos["33"];
			} else {
				$query .= ", 0";
			}
			
			if (array_key_exists("34" , $this->segmentos) &&  "" != $this->segmentos["34"]) {
				$query .= ", " . $this->segmentos["34"];
			} else {
				$query .= ", 0";
			}
			
			if (array_key_exists("35" , $this->segmentos) && "" != $this->segmentos["35"]) {
				$query .= ", '" . $this->convierteAFecha($this->segmentos["35"]) . "'";
			} else {
				$query .= ", now()";
			}
			
			$query .= ", " . $this->idSegmentoHD . ", " . $sfolio . ");";
		} catch (\Exception $e) {
			$this->idError = -6;
			$this->descError = "Error al interpretar el segmento CR de la respuesta de buro - " . $e->getMessage() . " [" . value . "]";
			return;
		}
		
		try{
			Log::info($query);
			$st = DB::insert($query);			
		} catch(\Exception $e) {
			$this->idError = -7;
			$this->descError = "Error al guardar segmento CR de la respuesta de buro - " . $e->getMessage();
            return;
		}
	}

	/*
	*procesa_CR_OLD
	*/
	private function procesa_CR_OLD($sfolio, $segmento) {
		$query = "";
		
		try {
			$this->segmentos = $this->getSubsegmentos($segmento);
			if (empty($this->segmentos)) {
				$this->idError = -1;
				$this->descError = "Error al interpretar el segmento CR de la respuesta de buro.";
				// throw new Exception("El segmento no tiene etiquetas.");
			}
			
			// RECUPERAMOS CUENTAS
			// -----------------------------------------------------------------------------------------------------
			try{
				$query = $this->insertQueryCuentas($this->segmentos, $sfolio);
				Log::info(query);
				$st = DB::insert($query);
			} catch(\Exception $e) {
				Log::info($e);
			}
		} catch (\Exception $e) {
			$this->idError = -1;
			$this->descError = "Error al interpretar el segmento CR de la respuesta de buro: " . $e->getMessage();
			return;
		}
	}
	
	/*
	*procesa_HI
	*/
	private  function procesa_HI($value, $sfolio) {
		$query = "INSERT INTO buro_consultapm_hi (id_segmento, rfc, periodo, saldo_vigente, saldo_vencido_1_29, saldo_vencido_30_59, " 
				. "saldo_vencido_60_89, saldo_vencido_90mas, calif_cartera, maximo_saldo_vencido, mayor_num_dias_vencido, id_segmento_hd, folio_consulta) VALUES (";

		try {
			$this->segmentos = $this->getSubsegmentos($value);
			if (empty($this->segmentos)) {
				$this->idError = -1;
				$this->descError = "Error al interpretar el segmento HI de la respuesta de buro.";
				throw new Exception("El segmento no tiene etiquetas.");
			}
			
			if (array_key_exists("HI" , $this->segmentos) && "" != $this->segmentos["HI"]) {
				$query .= $this->segmentos["HI"];
			} else {
				$query .= "-1";
			}
			
			if (array_key_exists("00" , $this->segmentos) && "" != $this->segmentos["00"]) {
				$query .= ", '" . $this->segmentos["00"] . "'";
			} else {
				$query .= ", ''";
			}
			
			if (array_key_exists("01" , $this->segmentos) &&  "" != $this->segmentos["01"]) {
				$query .= ", '" . $this->segmentos["01"] . "'";
			} else {
				$query .= ", ''";
			}
			
			if (array_key_exists("02" , $this->segmentos) && "" != $this->segmentos["02"]) {
				$query .= ", " . $this->segmentos["02"];
			} else {
				$query .= ", 0";
			}
			
			if (array_key_exists("03" , $this->segmentos) && "" != $this->segmentos["03"]) {
				$query .= ", " . $this->segmentos["03"];
			} else {
				$query .= ", 0";
			}
			
			if (array_key_exists("04" , $this->segmentos) && ! "" != $this->segmentos["04"]) {
				$query .= ", " . $this->segmentos["04"];
			} else {
				$query .= ", 0";
			}
			
			if (array_key_exists("05" , $this->segmentos) && "" != $this->segmentos["05"]) {
				$query .= ", " . $this->segmentos["05"];
			} else {
				$query .= ", 0";
			}
			
			if (array_key_exists("06" , $this->segmentos) && "" != $this->segmentos["06"]) {
				$query .= ", " . $this->segmentos["06"];
			} else {
				$query .= ", 0";
			}
			
			if (array_key_exists("07" , $this->segmentos) && "" != $this->segmentos["07"]) {
				$query .= ", '" . $this->segmentos["07"] ."'";
			} else {
				$query .= ", ''";
			}
			
			if (array_key_exists("08" , $this->segmentos) && "" != $this->segmentos["08"]) {
				$query .= ", " . $this->segmentos["08"];
			} else {
				$query .= ", 0";
			}
			
			if (array_key_exists("09" , $this->segmentos) && "" != $this->segmentos["09"]) {
				$query .= ", " . $this->segmentos["09"];
			} else {
				$query .= ", 0";
			}
			
			$query .= ", " . $this->idSegmentoHD . ", " . $sfolio . ");";
		} catch (\Exception $e) {
			$this->idError = -6;
			$this->descError = "Error al interpretar el segmento HI de la respuesta de buro - " . $e->getMessage() . " [" . $value . "]";
			return;
		}
		
		try{
			Log::info($query);
			$st = DB::insert($query);
		} catch(\Exception $e) {
			$this->idError = -7;
			$this->descError = "Error al guardar segmento HI de la respuesta de buro - " . $e->getMessage();
            return;
		}
	}

	//@SuppressWarnings("unused")
	/*
	*procesa_HI_OLD
	*/
	private function procesa_HI_OLD($sfolio, $segmento) {
		$query = "";
		
		try {
			$this->segmentos = $this->getSubsegmentos($segmento);
			if (empty($this->segmentos)) {
				$this->idError = -1;
				$this->descError = "Error al interpretar el segmento CR de la respuesta de buro.";
				throw new Exception("El segmento no tiene etiquetas.");
			}
			
			// RECUPERAMOS CONSULTAS EFECTUADAS (HISTORICO)
			// -----------------------------------------------------------------------------------------------------
			try{
				$query = $this->insertQueryConsultasEfectuadas($this->segmentos, $sfolio);
				Log::info($query);
				$st = DB::insert($query);				
			} catch(\Exception $e) {
				Log::info($e);
			}
		} catch (\Exception $e) {
			$this->idError = -1;
			$this->descError = "Error al interpretar el segmento HI de la respuesta de buro: " . $e->getMessage();
			return;
		}
	}
	
	/*
	*procesa_CO
	*/
	private function procesa_CO($value, $sfolio) {
		$query = "INSERT INTO buro_consultapm_co (id_segmento, rfc, num_consecutivo_usuario, saldo_total, saldo_vigente, " 
				. "saldo_vencido, saldo_vencido_1_29, saldo_vencido_30_59, saldo_vencido_60_89, saldo_vencido_90_119, saldo_vencido_120_179, "
				. "saldo_vencido_180mas, ultimo_periodo_actualizado, maximo_saldo_vencido, saldo_promedio, historico_pagos, registro_impugnado, "
				. "id_segmento_hd, folio_consulta) VALUES (";

		try {
			$this->segmentos = $this->getSubsegmentos($value);
			if (empty($this->segmentos)) {
				$this->idError = -1;
				$this->descError = "Error al interpretar el segmento CO de la respuesta de buro.";
				throw new Exception("El segmento no tiene etiquetas.");
			}
			
			if (array_key_exists("CO" , $this->segmentos) &&  "" != $this->segmentos["CO"]) {
				$query .= $this->segmentos["CO"];
			} else {
				$query .= "-1";
			}
			
			if (array_key_exists("00" , $this->segmentos) && "" != ($this->segmentos["00"])) {
				$query .= ", '" . $this->segmentos["00"] . "'";
			} else {
				$query .= ", ''";
			}
			
			if (array_key_exists("01" , $this->segmentos) && "" != $this->segmentos["01"]) {
				$query .= ", " . $this->segmentos["01"];
			} else {
				$query .= ", 0";
			}
			
			if (array_key_exists("02" , $this->segmentos) && "" != $this->segmentos["02"]) {
				$query .= ", " . $this->segmentos["02"];
			} else {
				$query .= ", 0";
			}
			
			if (array_key_exists("03" , $this->segmentos) && "" != $this->segmentos["03"]) {
				$query .= ", " . $this->segmentos["03"];
			} else {
				$query .= ", 0";
			}
			
			if (array_key_exists("04" , $this->segmentos) && "" != $this->segmentos["04"]) {
				$query .= ", " . $this->segmentos["04"];
			} else {
				$query .= ", 0";
			}
			
			if (array_key_exists("05" , $this->segmentos) && "" != $this->segmentos["05"]) {
				$query .= ", " . $this->segmentos["05"];
			} else {
				$query .= ", 0";
			}
			
			if (array_key_exists("06" , $this->segmentos) && "" != $this->segmentos["06"]) {
				$query .= ", " . $this->segmentos["06"];
			} else {
				$query .= ", 0";
			}
			
			if (array_key_exists("07" , $this->segmentos) && "" != $this->segmentos["07"]) {
				$query .= ", " . $this->segmentos["07"];
			} else {
				$query .= ", 0";
			}
			
			if (array_key_exists("08" , $this->segmentos) && "" != ($this->segmentos["08"])) {
				$query .= ", " . $this->segmentos["08"];
			} else {
				$query .= ", 0";
			}
			
			if (array_key_exists("09" , $this->segmentos) && ""  != $this->segmentos["09"]) {
				$query .= ", " . $this->segmentos["09"];
			} else {
				$query .= ", 0";
			}
			
			if (array_key_exists("10" , $this->segmentos) && "" != $this->segmentos["10"]) {
				$query .= ", " . $this->segmentos["10"];
			} else {
				$query .= ", 0";
			}
			
			if (array_key_exists("11" , $this->segmentos) && "" != $this->segmentos["11"]) {
				$query .= ", '" . $this->segmentos["11"] . "'";
			} else {
				$query .= ", ''";
			}
			
			if (array_key_exists("12" , $this->segmentos) && "" != ($this->segmentos["12"])) {
				$query .= ", " . $this->segmentos["12"];
			} else {
				$query .= ", 0";
			}
			
			if (array_key_exists("13" , $this->segmentos) && "" != $this->segmentos["13"]) {
				$query .= ", " . $this->segmentos["13"];
			} else {
				$query .= ", 0";
			}
			
			if (array_key_exists("14" , $this->segmentos) && "" != $this->segmentos["14"]) {
				$query .= ", '" . $this->segmentos["14"] . "'";
			} else {
				$query .= ", ''";
			}
			
			if (array_key_exists("15" , $this->segmentos) && "" != $this->segmentos["15"]) {
				$query .= ", '" . $this->segmentos["15"] . "'";
			} else {
				$query .= ", ''";
			}
			
			$query .= ", " . $this->idSegmentoHD . ", " . $sfolio . ");";
		} catch (\Exception $e) {
			$this->idError = -6;
			$this->descError = "Error al interpretar el segmento CO de la respuesta de buro - " . $e->getMessage() . " [" . $value . "]";
			return;
		}
		
		try{
			Log::info($query);
			$st = DB::insert($query);
		} catch(\Exception $e) {
			$this->idError = -7;
			$this->descError = "Error al guardar segmento CO de la respuesta de buro - " . $e->getMessage();
            return;
		}
	}

	/*
	*procesa_FD
	*/
	private function procesa_FD($value, $sfolio) {
		$query = "INSERT INTO buro_consultapm_fd (id_segmento, clave, nombre, valor_caracteristica, codigo_error, id_segmento_hd, folio_consulta) VALUES (";

		try {
			$this->segmentos = $this->getSubsegmentos($value);
			if (empty($this->segmentos)) {
				$this->idError = -1;
				$this->descError = "Error al interpretar el segmento FD de la respuesta de buro.";
				throw new Exception("El segmento no tiene etiquetas.");
			}
			
			if (array_key_exists("FD" , $this->segmentos) && "" != $this->segmentos["FD"]) {
				$query .= $this->segmentos["FD"];
			} else {
				$query .= "-1";
			}
			
			if (array_key_exists("00" , $this->segmentos) && "" != $this->segmentos["00"]) {
				$query .= ", '" . $this->segmentos["00"] . "'";
				$this->segmentos.put("01", $this->getNombreClaveCalifica($this->segmentos["00"]));
			} else {
				$query .= ", ''";
			}
			
			if (array_key_exists("01" , $this->segmentos) &&  "" != $this->segmentos["01"]) {
				$query .= ", '" . $this->segmentos["01"] . "'";
			} else {
				$query .= ", ''";
			}
			
			if (array_key_exists("02" , $this->segmentos) && "" != $this->segmentos["02"]) {
				$query .= ", '" . $this->segmentos["02"] . "'";
			} else {
				$query .= ", ''";
			}
			
			if (array_key_exists("03" , $this->segmentos) && ""  != $this->segmentos["03"]) {
				$query .= ", '" . $this->segmentos["03"] . "'";
			} else {
				$query .= ", ''";
			}
			
			$query .= ", " . $this->idSegmentoHD . ", " . $sfolio . ");";
		} catch (\Exception $e) {
			$this->idError = -6;
			$this->descError = "Error al interpretar el segmento FD de la respuesta de buro - " . $e->getMessage() . " [" . $value . "]";
			return;
		}
		
		try{
			Log::info($query);
			$st = DB::insert($query);			
		} catch(\Exception $e) {
			$this->idError = -7;
			$this->descError = "Error al guardar segmento FD de la respuesta de buro - " . $e->getMessage();
            return;
		}
	}

	/*
	*procesa_CN
	*/
	private function procesa_CN($value, $sfolio) {
		$query = "INSERT INTO buro_consultapm_cn (id_segmento, rfc, fecha_consulta, tipo_usuario, id_segmento_hd, folio_consulta) VALUES (";

		try {
			$this->segmentos = $this->getSubsegmentos($value);
			if (empty($this->segmentos)) {
				$this->idError = -1;
				$this->descError = "Error al interpretar el segmento CN de la respuesta de buro.";
				throw new Exception("El segmento no tiene etiquetas.");
			}
			
			if (array_key_exists("CN" , $this->segmentos) && "" != $this->segmentos["CN"]) {
				$query .= $this->segmentos["CN"];
			} else {
				$query .= "-1";
			}
			
			if (array_key_exists("00" , $this->segmentos) &&  "" != $this->segmentos["00"]) {
				$query .= ", '" . $this->segmentos["00"] . "'";
			} else {
				$query .= ", ''";
			}
			
			if (array_key_exists("01" , $this->segmentos) && "" != $this->segmentos["01"]) {
				$query .= ", '" . $this->convierteAFecha($this->segmentos["01"]) . "'";
			} else {
				$query .= ", NOW()";
			}
			
			if (array_key_exists("02" , $this->segmentos) && "" != $this->segmentos["02"]) {
				$query .= ", '" . $this->segmentos["02"] . "'";
			} else {
				$query .= ", ''";
			}
			
			$query .= ", " . $this->idSegmentoHD . ", " . $sfolio . ");";
		} catch (\Exception $e) {
			$this->idError = -6;
			$this->descError = "Error al interpretar el segmento CN de la respuesta de buro - " . $e->getMessage() . " [" . $value . "]";
			return;
		}
		
		try{
			Log::info($query);
			$st = DB::insert($query);			
		} catch(\Exception $e) {
			$this->idError = -7;
			$this->descError = "Error al guardar segmento CN de la respuesta de buro - " . $e->getMessage();
            return;
		}
	}

	/*
	*procesa_ER
	*/
	private function  procesa_ER($value, $sfolio) {
		$query = "INSERT INTO buro_consultapm_er (id_segmento, producto, segmento_req_no_proporcionado, campo_req_no_propercionado, " 
				. "rfc_invalido, error_integrar_datos, error_generar_respuesta, problema_conexion, error_ejecucion, error_desconocido, id_segmento_hd, folio_consulta) VALUES (";

		try {
			$this->segmentos = $this->getSubsegmentos($value);
			if (empty($this->segmentos)) {
				$this->idError = -1;
				$this->descError = "Error al interpretar el segmento ER de la respuesta de buro.";
				throw new Exception("El segmento no tiene etiquetas.");
			}
			
			// id_segmento
			if (array_key_exists(" " , $this->segmentos) && "" != $this->segmentos["ER"]) {
				$query .= $this->segmentos["ER"];
			} else {
				$query .= "-1";
			}
			
			// producto
			if (array_key_exists("00" , $this->segmentos) && "" != $this->segmentos["00"]) {
				$query .= ", '" . $this->segmentos["00"] . "'";
				$this->descError = $this->segmentos["00"];
			} else {
				$query .= ", ''";
			}
			
			// segmento_req_no_proporcionado
			if (array_key_exists("01" , $this->segmentos) && "" != $this->segmentos["01"]) {
				$query .= ", '" . $this->segmentos["01"] . "'";
				$this->descError = $this->segmentos["01"];
			} else {
				$query .= ", ''";
			}
			
			if (array_key_exists("02" , $this->segmentos) && "" != $this->segmentos["02"]) {
				$query .= ", '" . $this->segmentos["02"] . "'";
				$this->descError = $this->segmentos["02"];
			} else {
				$query .= ", ''";
			}
			
			if (array_key_exists("03" , $this->segmentos) && "" != $this->segmentos["03"]) {
				$query .= ", '" . $this->segmentos["03"] . "'";
				$this->descError = $this->segmentos["03"];
			} else {
				$query .= ", ''";
			}
			
			if (array_key_exists("04" , $this->segmentos) &&  "" != $this->segmentos["04"]) {
				$query .= ", '" . $this->segmentos["04"] . "'";
				$this->descError = $this->segmentos["04"];
			} else {
				$query .= ", ''";
			}
			
			if (array_key_exists("05" , $this->segmentos) && "" != $this->segmentos["05"]) {
				$query .= ", '" . $this->segmentos["05"] . "'";
				$this->descError = $this->segmentos["05"];
			} else {
				$query .= ", ''";
			}
			
			if (array_key_exists("06" , $this->segmentos) && "" != $this->segmentos["06"]) {
				$query .= ", '" . $this->segmentos["06"] . "'";
				$this->descError = $this->segmentos["06"];
			} else {
				$query .= ", ''";
			}
			
			if (array_key_exists("07" , $this->segmentos) && "" != $this->segmentos["07"]) {
				$query .= ", '" . $this->segmentos["07"] . "'";
				$this->descError = $this->segmentos["07"];
			} else {
				$query .= ", ''";
			}
			
			if (array_key_exists("08" , $this->segmentos) &&  "" != $this->segmentos["08"]) {
				$query .= ", '" . $this->segmentos["08"] . "'";
				$this->descError = $this->segmentos["08"];
			} else {
				$query .= ", ''";
			}
			
			$query .= ", " . $this->idSegmentoHD . ", " . $sfolio . ");";
		} catch (\Exception $e) {
			$this->idError = -6;
			$this->descError = "Error al interpretar el segmento ER de la respuesta de buro - " . $e->getMessage() . " [" . $value . "]";
			return;
		}
		
		try{
			Log::info($query);
			$st = DB::insert($query);			
		} catch(\Exception $e) {
			$this->idError = -7;
			$this->descError = "Error al guardar segmento ER de la respuesta de buro - " . $e->getMessage();
            return;
		}
	}

	/*
	*procesa_CI
	*/
	private function procesa_CI($value, $sfolio) {
		$query = "INSERT INTO buro_consultapm_ci (id_segmento, id_consulta, id_transaccion, folio_consulta) VALUES (";
		
		try {
			$this->segmentos = $this->getSubsegmentos($value);
			if (empty($this->segmentos)) {
				$this->idError = -1;
				$this->descError = "Error al interpretar el segmento CI de la respuesta de buro.";
				throw new Exception("El segmento no tiene etiquetas.");
			}
			
			// id_segmento
			if (array_key_exists("CI" , $this->segmentos) && "" != $this->segmentos["CI"]) {
				$query .= $this->segmentos["CI"];
			} else {
				$query .= "-1";
			}
			
			// id_consulta
			if (array_key_exists("00" , $this->segmentos) && "" != $this->segmentos["00"]) {
				$query .= ", " . $this->segmentos["00"];
			} else {
				$query .= ", -1";
			}
			
			// id_transaccion
			if (array_key_exists("02" , $this->segmentos) &&  "" != $this->segmentos["02"]) {
				$query .= ", " . $this->segmentos["02"];
			} else {
				$query .= ", -1";
			}
			
			$query .= ", " . $sfolio . ");";
		} catch (\Exception $e) {
			$this->idError = -6;
			$this->descError = "Error al interpretar el segmento CI de la respuesta de buro - " . $e->getMessage() . " [" . $value . "]";
			return;
		}
		
		try{
			Log::info($query);
			$st = DB::insert($query);			
		} catch(\Exception $e) {
			$this->idError = -7;
			$this->descError = "Error al guardar segmento CI de la respuesta de buro - " . $e->getMessage();
            return;
		}
	}
	
	/*
	*procesa_CI_OLD
	*/
	private function procesa_CI_OLD($sfolio, $segmento) {
		$query = "";
		
		try {
			$this->segmentos = $this->getSubsegmentos($segmento);
			if (empty($this->segmentos)) {
				$this->idError = -1;
				$this->descError = "Error al interpretar el segmento CI de la respuesta de buro.";
				throw new Exception("El segmento no tiene etiquetas.");
			}
			
			// id_transaccion
			if (!array_key_exists("02" , $this->segmentos) || "" != $this->segmentos["02"]) {
				$this->idError = -1;
				$this->descError = "El segmento CI no contiene la etiqueta 02 correspondiente al ID TRANSACCION asignado por Buro de Credito.";
				throw new Exception($this->descError);
			}
			
			// RECUPERAMOS id_transaccion QUE SE INTERPRETA COMO CUENTA BURO Y CONTROL
			// -----------------------------------------------------------------------------------------------------
			try{
				$query = "UPDATE consultas_circulo " 
					  . "SET control = '" . $this->segmentos["02"] . "', cuenta_buro = ". $this->segmentos["02"] . ", fecha_creacion = CURRENT_TIMESTAMP "
					  . "WHERE folioconsulta = " . $sfolio;
				Log::info($query);
				DB::update($query);	  					
			} catch(\Exception $e) {
				Log::info($e);
			}
		} catch (\Exception $e) {
			$this->idError = -1;
			$this->descError = "Error al interpretar el segmento CI de la respuesta de buro: " . $e->getMessage();
			return;
		}
	}
	
	// ----------------------------------------------------------------------------------
	// UTILERIAS
	// ----------------------------------------------------------------------------------
	
	/*
	*boolean getCredenciales  
	*/
	private function getCredenciales($idSucursal) {
		$sSql = "";
		
		try {
			$this->host = "";
			$this->port = "";
			$this->user = "";
			$this->pass = "";
			$this->code = "";
			$sSql = "SELECT TRIM(COALESCE(usuario_buro_pm, '')) AS user, "
					. "    TRIM(COALESCE(contrasena_buro_pm, '')) AS pass, "
					. "    TRIM(COALESCE(servidor_buro_pm, '')) AS host, "
					. "    TRIM(COALESCE(puerto_buro_pm, '')) AS port, "
					. "    TRIM(COALESCE(codigo_institucion_buro_pm, '')) AS code "
					. "FROM agente a INNER JOIN cat_empresas e ON e.clave = a.empresa "
					. "WHERE a.agente_id = " . $idSucursal . ";";
			$rs = DB::select($sSql);			
			if (empty($rs))
				return false;

			$this->host = $rs->host;
			$this->port = $rs->port;
			$this->user = $rs->user;
			$this->pass = $rs->pass;
			$this->code = $rs->code;

			if ("" == $this->host) throw new Exception("No tiene asignado el servidor para consulta de personas morales con buro de credito");
			if ("" == $this->port) throw new Exception("No tiene asignado el puerto para consulta de personas morales con buro de credito");
			if ("" == $this->user) throw new Exception("No tiene asignado el usuario para consulta de personas morales con buro de credito");
			if ("" == $this->pass) throw new Exception("No tiene asignado la contrase�a para consulta de personas morales con buro de credito");
			if ("" == $this->code) throw new Exception("No tiene asignado el codigo de institucion para consulta de personas morales con buro de credito");
			
			return true;
		} catch (\Exception $e) {
			$this->idError = -2;
			$this->descError = "Error al obtener las credenciales para consulta a buro: " . $e->getMessage();
			return false;
		}
	}
	
	/*
	* getClientesDatos
	*/
	private function getClientesDatos($solicitantes) {
		$sSql = "";
		
		try {
			$sSql = "SELECT s.numero AS id"
					. "    , TRIM(COALESCE(s.rfc1, '') || COALESCE(s.rfc2, '') || COALESCE(s.rfc3, '')) AS rfc"
					. "    , TRIM(COALESCE(s.nombre, '')) AS nombre1"
					. "    , '' AS nombre2"
					. "    , CASE s.t_persona "
					. "        WHEN 'F' THEN CASE "
					. "            WHEN COALESCE(TRIM(s.apellidos), '') <> '' THEN COALESCE(TRIM(s.apellidos), '') "
					. "            WHEN COALESCE(TRIM(s.apellidos), '') = '' AND COALESCE(TRIM(s.apellido_m), '') <> '' THEN COALESCE(TRIM(s.apellido_m), '') "
					. "            ELSE '' END "
					. "        ELSE '' "
					. "        END AS ap_paterno"
					. "    , CASE s.t_persona "
					. "        WHEN 'F' THEN CASE "
					. "            WHEN COALESCE(TRIM(s.apellidos), '') = '' AND COALESCE(TRIM(s.apellido_m), '') <> '' THEN 'NO PROPORCIONADO' "
					. "            ELSE COALESCE(TRIM(s.apellido_m), '') END "
					. "        ELSE 'NO PROPORCIONADO' "
					. "        END AS ap_materno"
					. "    , TRIM(s.t_persona) AS tipo_persona"
					. "    , TRIM(COALESCE(s.domicilio, '')) AS domicilio1"
					. "    , '' AS domicilio2"
					. "    , COALESCE(col.cp, '00000') AS codigo_postal"
					. "    , TRIM(COALESCE(col.nombre, '')) AS colonia"
					. "    , TRIM(COALESCE(loc.descripcion, '')) AS ciudad"
					. "    , TRIM(COALESCE(edo.clave_buro, '')) AS estado"
					. "    , CASE WHEN edo.clave IS NOT NULL THEN 'MX' ELSE '' END AS pais "
					. "FROM solicitante s"
					. "    LEFT JOIN colonia col on col.clave = s.colonia"
					. "    LEFT JOIN localidades loc on loc.clave = col.localidad_id"
					. "    LEFT JOIN catmpio mun on mun.cmpiompio=loc.catmpio_id"
					. "    LEFT JOIN estatal edo on edo.clave = mun.estado_id "
					. "WHERE numero IN (". $solicitantes . ");";
			$st = DB::select($sSql);
			return  $st;
		} catch (\Exception $e) {
			return null;
		}
	}

	/*
	*inserta_tabla_consultacirculo
	*/
	private  function inserta_tabla_consultacirculo($consultaEnviada, $res, $idsol, $noEtapa, $valorEtapa, 
			$ProductoRequerido, $TipoCuenta, $ClaveUnidadMonetaria, $ImporteContrato, 
			$usuario_id_sol,$usuario_id_aut, $computadora,$confirma, $sucursal, $error) {
		$sValorn = "";
		$sInsert = "";
		
		try {
			try {
				$sValorn = "select nextval('Consultas_circulo_s')";
				$res2 = DB::select($sValorn);				
				
				$iFolioid = reset($res2);		
			} catch(\Exception $e) {
				Log::info($e);
				$iFolioid = -1;
			}

			if($iFolioid != -1) {

				$pattern = "'";
                $replacement = " ";
                preg_replace($pattern, $replacement, $res);

				$sInsert = "Insert into consultas_circulo(folioconsulta, buro, respuestaxml, consultaxml, solicitante, noetapa, valoretapa, tipocuenta, claveunidadmonetaria, importecontrato, usuario_id_sol, usuario_id_aut, computadora, confirma, sucursal, productorequerido, fecha_creacion, error) values("
					. $iFolioid . ",'B','" . $res ."','" . $consultaEnviada . "','" . $idsol . "'," . String.valueOf($noEtapa) . ",'" . $valorEtapa . "','" . $TipoCuenta . "','" . $ClaveUnidadMonetaria . "',". $ImporteContrato . ",". $usuario_id_sol . "," . $usuario_id_aut . ",'" . $computadora . "'," . $confirma . ",'" . $sucursal . "'," . $ProductoRequerido . ", CURRENT_TIMESTAMP, '" . $error . "')";

				$st = DB::insert($sInsert);
				Log::info($sInsert);
			} 
		} catch(\Exception $e){
			Log::info($e);
		}

		return $iFolioid;
	}
	
	/*
	*actualizaError_consultacirculo
	*/
	private function actualizaError_consultacirculo($sfolio, $replaceError) {
		$query = "";
		
		try {
			if ($this->idError != 0) {
				if ($replaceError)
					$query = "Update consultas_circulo set error = '" . $this->idError . " - " . $this->descError . "' , fecha_creacion = CURRENT_TIMESTAMP where folioconsulta = " . $sfolio;
				else
					$query = "Update consultas_circulo set error = CASE TRIM(COALESCE(error, '')) WHEN '' THEN '' ELSE COALESCE(error, '') || ' :: ' END || '" . $this->idError . " - " . $this->descError . "' , fecha_creacion = CURRENT_TIMESTAMP where folioconsulta = " . $sfolio;
				$st = DB::update($query);
			}
			
			if ($this->idError == 0 && $replaceError) {
				$query = "Update consultas_circulo set error = '' , fecha_creacion = CURRENT_TIMESTAMP where folioconsulta = " . $sfolio;
				$st = DB::update($query);
			}
			
			return true;
		} catch(\Exception $e) {
			 Log::info($e);
			return false;
		}
	}
	
	/*
	*generaIdentificadorConsulta
	*/
	private  function generaIdentificadorConsulta() {
		$rs = null;
		$query = "";
		$id = 0;
		
		try {
			
			$query = "select 1 from pg_class where relkind = 'S'  and relname = 'identificadorconsulta_seq';";
			$rs= DB::select($query);			
			if (!empty($rs)) {
				$query = "create sequence identificadorconsulta_seq increment 1 minvalue 1 maxvalue 9223372036854775807 start 1;";
				$st = DB::create($query);					
			}
				
			$query ="SELECT COALESCE(nextval('identificadorConsulta_seq'), -1) AS id;";
			$rs = DB::select($query);
			
			if (!empty($rs))
				$id = reset($rs);
			else
				$id = -1;			
			
		} catch(\Exception $e) {
			Log::info($e);
			$id = -1;
		}
		
		return $id;
	}
	
	/*
	*campo
	*/
	private  function campo($etiqueta, $value, $tipo, $maxLenght, $longitudFija, $requerido, $relleno) {
		$resRelleno = "";
		$result = "";
		$posRelleno = 0;
		
		if ($value == null) {
			if ($requerido) {
				for ($i = 0; i < $maxLenght; $i++)
					$value .= " ";
			} else {
				$value = "";
			}
		}
		
		if (""  == $value && ! $requerido)
			return "";
		
		if ("" != $value && strlen($value) == $maxLenght) 
			return $etiqueta . $this->lenghtString($value) . $value;
		
		if ($relleno == null)
			$relleno = "";
		
		// Truncamos el valor al maximo establecido en caso de ser necesario
		if ($maxLenght > 0 && strlen($value) > $maxLenght) {
			$value = substr($value , 0 , $maxLenght);
		}
		
		if ($longitudFija) { 
			if ( "" != $relleno) {
				$posRelleno = $maxLenght - strlen($value);
				for ($i = 0; $i < $posRelleno; $i++)
					$resRelleno .= $relleno;
			}
			
			if ("N" == $tipo && "0" == $relleno) {
				$result = $resRelleno . $value;
			} else if ("A" == $tipo && " "  == $relleno) {
				$result = $value . $resRelleno;
			} else if ("AN" == $tipo && " "  == $relleno) {
				$result = $value . $resRelleno;
			} else {
				$result = $value;
			}
		} else {
			if ("" != $relleno) {
				$posRelleno = $maxLenght - strlen($value);
				for ($i = 0; $i < $posRelleno; $i++)
					$resRelleno .= $relleno;
			}
			
			if ("" != $resRelleno) {
				if ("N" == $tipo && "0" == $relleno) {
					$result = $resRelleno . $value;
				} else if ("A" == $tipo && " " == $relleno) {
					$result = $value . $resRelleno;
				} else if ("AN" == $tipo && " " == $relleno) {
					$result = $value . $resRelleno;
				}
			} else {
				$result = $value;
			}
		}
		
		return $etiqueta . $this->lenghtString($result) . $result;
	}
	
	/*
	*lenghtString
	*/
	private function  lenghtString($value) {
		if (strlen(strlen($value)) < 10)
			return "0" . strlen($value);
		return strlen($value);
	}

	/*
	*getSegmentos
	*/
	private function getSegmentos($value) {
		$segmentos = array();
		
		if ($this->startswidth(trim($value),"HD")) {
			// modo automatico			
			if (strpos($value, "\n") !== false) {
				$splitted = split("\n" , $value);
				for ($i = 0; $i < strlen($splitted); $i++) {
					if ("" != trim($splitted[$i]))
						array_push($segmentos, trim($splitted[$i]));
				}	
			} else {
				
			}
		}
		
		return $segmentos;
	}

	/*
	*getSubsegmentos
	*/
	private  function getSubsegmentos($value) {
		$ss = array();
		$etiqueta = "";
		$data = "";
		$lenStr = "";
		$longitud = 0;
		$pos = 0;
		
		while (strlen($value > 0)) {
			// obtenemos etiqueta
			$etiqueta = substr($value, $pos, 2 );
			$pos += 2;
			
			// obtenemos longitud del dato
			$lenStr = substr($value, $pos, $pos + 2);
			$longitud = (int)$lenStr;
			$pos += 2;
			
			// obtenemos el dato
			if (strlen($value) < ($pos + $longitud)) {
				$data = substr($value, $pos , strlen($value));
				$ss[$etiqueta] = $data;
				$value = "";
			} else {
				$data = substr($value , $pos, $pos + $longitud);
				$ss[$etiqueta] = $data;				 
				$value = substr($value , $longitud + $pos);
			}
			
			$pos = 0;
		}
		
		return $ss;
	}
	
	/*
	*getFechaNacimientoFromRFC
	*/
	private  function getFechaNacimientoFromRFC($rfc) {
		$fecha = '';
		$dt = '';
		
		try {
			if (strlen($rfc == 13)) {
				$fecha = substr($rfc , 4, 10);
			} else {
				$fecha = substr($rfc , 3, 9);
			}
			
			$dt = date('yyMMdd', strtotime($fecha)); 
			
			return date('MM/dd/yyyy', strtotime($dt));
		} catch (\Exception $ex) {
			return "null";
		}
	}
	
	/*
	*convierteAFecha
	*/
	private  function convierteAFecha($value) {
		return $this->convierteAFecha_1($value, "ddMMyyyy", "MM/dd/yyyy");
	}

	/*
	*convierteAFecha
	*/
	private  function convierteAFecha_1($value, $formatoEntrada, $formatoSalida) {
		
		try {
			if ("" == $value) return "null";
			$dt = date($formatoEntrada, strtotime($value));
//			$formatter.applyPattern($formatoSalida);
			return date($formatoSalida, strtotime($dt));
		} catch (\Exception $ex) {
			return "null";
		}
	}

	/*
	*insertQueryPersona
	*/
	private  function insertQueryPersona($segmentos, $sfolio) {
		$insert = "";
		
		$insert = "Insert into circulo_personas(folioconsultaotorgante, nombres, apellidopaterno, apellidomaterno, apellidoadicional, " 
				. "fechanacimiento, rfc, nacionalidad, estadocivil, numerodependientes, sexo, claveife) values(";
		
		// folio
		$insert .= $sfolio;
		
		// nombre
		if (array_key_exists("03" , $segmentos) && "" != $segmentos['03']) {
			// segundo nombre
			if (array_key_exists("04", $segmentos) &&  "" != $segmentos['04']) {
				$insert .= ", '" . $segmentos["03"] . " " . segmentos["04"] . "'";
			} else {
				$insert .= ", '" . $segmentos["03"] . "'";
			}
		} else {
			$insert .= ", null";
		}
		
		// apellido paterno
		if (array_key_exists("05", $segmentos) &&  "" != $segmentos["05"] && "NO PROPORCIONADO" != $segmentos["05"]) {
			$insert .= ", '" . $segmentos["05"] . "'";
		} else {
			$insert .= ", null";
		}
		
		// apellido paterno
		if (array_key_exists("05", $segmentos) &&  "" != $segmentos["05"] &&  "NO PROPORCIONADO" != $segmentos["05"]) {
			$insert .= ", '" . $segmentos["05"] . "'";
		} else {
			$insert .= ", null";
		}
		
		// apellido adicional
		$insert .= ", null";
		
		// fecha nacimiento
		if (array_key_exists("01", $segmentos) &&  "" != $segmentos["01"]) {
			$insert .= ", '" . getFechaNacimientoFromRFC(segmentos.get("01")) . "'";
		} else {
			$insert .= ", null";
		}
		
		// rfc
		if (array_key_exists("01", $segmentos) &&  "" != $segmentos["01"]) {
			$insert .= ", '" . $segmentos["01"] . "'";
		} else {
			$insert .= ", null";
		}
		
		// nacionalidad
		if (array_key_exists("18", $segmentos) && "" != $segmentos["18"]) {
			$insert .= ", '" . $segmentos["18"] . "'";
		} else {
			$insert .= ", null";
		}
		
		// estado civil
		$insert .= ", null";
		
		// numero dependientes
		$insert += ", null";
		
		// sexo
		$insert .= ", null";
		
		// ife
		
		$insert .= ", null";
		
		// fin de insert
		$insert .= ");";
		
		return $insert;
	}
	
	/*
	*insertQueryDomicilio
	*/
	private function insertQueryDomicilio($segmentos, $sfolio) {
		$insert = "Insert into circulo_domicilios(folioconsultaotorgante, direccion, ciudad, estado, cp, fecharesidencia, telefono, " 
				. "delegacionmunicipio, coloniapoblacion, fecharegistrodomicilio) values(";

		// folio
		$insert .= $sfolio;
		
		// direccion 1 y 2
		if (array_key_exists("07", $segmentos) && "" != $segmentos["07"]) {
			if (array_key_exists("08", $segmentos) && "" != $segmentos["08"]) {
				$insert .= ", '" . $segmentos["07"] . " " . $segmentos["08"] . "'";
			} else {
				$insert .= ", '" . $segmentos["07"] . "'";
			}
		} else {
			$insert .= ", null";
		}
		
		// ciudad
		if (array_key_exists("11", $segmentos) && "" != $segmentos["11"]) {
			$insert .= ", '" . $segmentos["11"] . "'";
		} else {
			$insert .= ", null";
		}
		
		// estado
		if (array_key_exists("12", $segmentos) && "" != $segmentos["12"]) {
			$insert .= ", '" . $segmentos["12"] . "'";
		} else {
			$insert .= ", null";
		}
		
		// codigo postal
		if (array_key_exists("13", $segmentos) && "" != $segmentos["13"]) {
			$insert .= ", '" . $segmentos["13"] . "'";
		} else {
			$insert .= ", null";
		}
		
		// fecha residencia
		$insert .= ", null";
		
		
		// telefono
		if (array_key_exists("15", $segmentos) && "" != $segmentos["15"]) {
			$insert .= ", '" . $segmentos["15"] . "'";
		} else {
			$insert .= ", null";
		}
		
		// delegacion o municipio
		if (array_key_exists("10", $segmentos) && "" != $segmentos["10"]) {
			$insert .= ", '" . $segmentos["10"] . "'";
		} else {
			$insert .= ", null";
		}
		
		// colonia
		if (array_key_exists("09", $segmentos) &&  "" != $segmentos["09"]) {
			$insert .= ", '" . $segmentos["09"] . "'";
		} else {
			$insert .= ", null";
		}
		
		// fecha registro domicilio
		$insert .= ", null";
		
		// fin de insert
		$insert .= ");";
		
		return $insert;
	}
	
	/*
	*insertQueryCuentas
	*/
	private function insertQueryCuentas($segmentos, $sfolio) {
		$insert = "Insert into circulo_cuentas(folioconsultaotorgante, fechaactualizacion, registroimpugnado, claveotorgante, nombreotorgante, " .
				"tiporesponsabilidad, tipocuenta, numeropagos, frecuenciapagos, fechaaperturacuenta, fechaultimopago, fechacierrecuenta, " .
				"saldoactual, saldovencido, numeropagosvencidos, historicopagos,claveunidadmonetaria, pagoactual, fechapeoratraso," .
				"saldovencidopeoratraso,limitecredito, montopagar, tipocredito, peoratraso, observacion, creditomaximo, fechaultimacompra, " .
				"fecharecientehistoricopagos, fechaantiguahistoricopagos, tipo_credito_id) values(";
		
		// folio
		$insert .= $sfolio;
		
		// fecha actualizacion
		if (array_key_exists("17", $segmentos) && "" != $segmentos["17"]) {
			$insert .= ", '" . $this->convierteAFecha_1($segmentos["17"], "yyyyMM", "yyyy-MM-dd") . "'";
		} else {
			$insert .= ", null";
		}
		
		// registro impugnado
		if (array_key_exists("25", $segmentos) && "" != $segmentos["25"]) {
			$insert .= ", '" . $segmentos["25"] . "'";
		} else {
			$insert .= ", ''";
		}
		
		// clave otorgante
		if (array_key_exists("01", $segmentos) && "" != $segmentos["01"]) {
			$insert .= ", '" . $segmentos["01"] . "'";
		} else {
			$insert .= ", ''";
		}
		
		// nombre otorgante
		if (array_key_exists("02", $segmentos) && "" != $segmentos["02"]) {
			$insert .= ", '" . $segmentos["02"] . "'";
		} else {
			$insert .= ", ''";
		}
		
		
		$insert .= ", ''";

		
		$insert .= ", ''";
		
		// numeropagos
		if (array_key_exists("27", $segmentos) && "" != $segmentos["27"]) {
			$insert .= ", " . $segmentos["27"];
		} else {
			$insert .= ", 0";
		}
		
		// frecuenciapagos
		if (array_key_exists("28", $segmentos) && "" != $segmentos["28"]) {
			$insert .= ", '" . $segmentos["28"] . "'";
		} else {
			$insert .= ", null";
		}

		// fechaaperturacuenta
		if (array_key_exists("05", $segmentos) && ! "".equals(segmentos.get("05"))) {
			$insert .= ", '" . $this->convierteAFecha_1($segmentos["05"], "ddMMyyyy", "MM/dd/yyyy") . "'";
		} else {
			$insert .= ", null";
		}

		// fechaultimopago
		if (array_key_exists("30", $segmentos) && "" != $segmentos["30"]) {
			$insert .= ", '" . $this->convierteAFecha_1($segmentos["30"], "ddMMyyyy", "MM/dd/yyyy") . "'";
		} else {
			$insert .= ", null";
		}

		// fechacierrecuenta
		if (array_key_exists("18", $segmentos) &&  "" != $segmentos["18"]) {
			$insert .= ", '" . $this->convierteAFecha_1($segmentos["18"], "ddMMyyyy", "MM/dd/yyyy") . "'";
		} else {
			$insert .= ", null";
		}

		// saldoactual
		if (array_key_exists("10", $segmentos) && "" != $segmentos["10"]) {
			$insert .= ", " . $segmentos["10"];
		} else {
			$insert .= ", 0";
		}

		// saldovencido
		if (array_key_exists("11", $segmentos) && "" != $segmentos["11"]) {
			$insert .= ", " . $segmentos["11"];
		} else {
			$insert .= ", 0";
		}
		
		$insert .= ", 0";

		// historicopagos
		if (array_key_exists("23", $segmentos) && "" != $segmentos["23"]) {
			$insert .= ", '" . $segmentos["23"] . "'";
		} else {
			$insert .= ", ''";
		}

		// claveunidadmonetaria
		if (array_key_exists("04", $segmentos) && "" != $segmentos["04"]) {
			if ("001" != $segmentos["04"])
				$insert .= ", 'MX'";
			else
				$insert .= ", '" . $segmentos["04"] . "'";
		} else {
			$insert .= ", ''";
		}

		
		$insert .= ", ''";

		
		$insert .= ", null";

		// saldovencidopeoratraso
		if (array_key_exists("11", $segmentos) && "" != $segmentos["11"]) {
			$valor = 0;
			if (array_key_exists("12", $segmentos) && "" != $segmentos["12"])
				$valor = floatval($segmentos["12"]);
			
			if (array_key_exists("13", $segmentos) && "" != $segmentos["13"]) 
				$valor = floatval($segmentos["13"]);
			
			if (array_key_exists("14", $segmentos) && ! "".equals(segmentos.get("14")))
				$valor = floatval($segmentos["14"]);
			
			if (array_key_exists("15", $segmentos) && "" != $segmentos["15"]) 
				$valor = floatval($segmentos["15"]);
			
			if (array_key_exists("16", $segmentos) && "" != $segmentos["16"]) 
				$valor = floatval($segmentos["16"]);
			
			$insert .= ", '" . $valor . "'";
		} else {
			$insert .= ", null";
		}

		$insert .= ", 0";

		// montopagar
		if (array_key_exists("29", $segmentos) &&  "" != $segmentos["29"]) {
			$insert .= ", " . $segmentos["29"];
		} else {
			$insert .= ", 0";
		}

		// tipocredito
		if (array_key_exists("09", $segmentos) && "" != $segmentos["09"]) {
			$insert .= ", '" . $this->getTipoCredito($segmentos["09"], false) . "'";
		} else {
			$insert .= ", ''";
		}

		
		$insert .= ", ''";

		// observacion
		if (array_key_exists("08", $segmentos) && "" != $segmentos["08"]) {
			$insert .= ", '" . $segmentos["08"] . "'";
		} else {
			$insert .= ", null";
		}

		// creditomaximo
		if (array_key_exists("34", $segmentos) && "" != $segmentos["34"]) {
			$insert .= ", " . $segmentos["34"];
		} else {
			$insert .= ", 0";
		}

		
		$insert .= ", null";

		
		$insert .= ", null";

		
		$insert .= ", null";

		
		$insert .= ", null";
		
		$insert .= ");";

				
		return $insert;
	}


	/*
	*insertQueryConsultasEfectuadas
	*/	
	private function insertQueryConsultasEfectuadas($segmentos, $sfolio) {
		$insert = "Insert into circulo_consultas_efectuadas(folioconsultaotorgante, fechaconsulta, claveotorgante, nombreotorgante, " 
				. "tipocredito, importecredito, tiporesponsabilidad, claveunidadmonetaria) values(";
		
		// folio
		$insert .= $sfolio;
		
		// fechaconsulta
		if (array_key_exists("NONE", $segmentos) && "" != $segmentos["NONE"]) {
			$insert .= ", '" . $segmentos["NONE"] . "'";
		} else {
			$insert .= ", null";
		}
		
		// claveotorgante
		if (array_key_exists("NONE", $segmentos) && "" != $segmentos["NONE"]) {
			$insert .= ", '" . $segmentos["NONE"] . "'";
		} else {
			$insert .= ", null";
		}
		
		// nombreotorgante
		if (array_key_exists("NONE", $segmentos) && "" != $segmentos["NONE"]) {
			$insert .= ", '" . $segmentos["NONE"] . "'";
		} else {
			$insert .= ", null";
		}
		
		// tipocredito
		if (array_key_exists("NONE", $segmentos) && "" != $segmentos["NONE"]) {
			$insert .= ", '" . $segmentos["NONE"] . "'";
		} else {
			$insert .= ", null";
		}
		
		// importecredito
		if (array_key_exists("NONE", $segmentos) && "" != $segmentos["NONE"]) {
			$insert .= ", '" . $segmentos["NONE"] . "'";
		} else {
			$insert .= ", null";
		}
		
		// tiporesponsabilidad
		if (array_key_exists("NONE", $segmentos) && "" != $segmentos["NONE"]) {
			$insert .= ", '" . $segmentos["NONE"] . "'";
		} else {
			$insert .= ", null";
		}
		
		// claveunidadmonetaria
		if (array_key_exists("NONE", $segmentos) && "" != $segmentos["NONE"]) {
			$insert .= ", '" . $segmentos["NONE"] . "'";
		} else {
			$insert .= ", null";
		}
		
		$insert .= ");";
		
		return $insert;
	}

	/*
	*getIdError
	*/	
	public function getIdError() {
		return idError;
	}

	/*
	*setIdError
	*/
	public function setIdError($idError) {
		$this->idError = $idError;
	}

	public function getDescError() {
		return $this->descError;
	}

	public function setDescError($descError) {
		$this->descError = $descError;
	}
	
	/*
	*cleanFolio
	*/
	private function cleanFolio($folio) {
		$query = "";
		
		try {
			$query = "DELETE FROM circulo_personas WHERE folioconsultaotorgante = " . $folio;
			$st = DB::delete($query);
		    
		    $query = "DELETE FROM circulo_domicilios WHERE folioconsultaotorgante = " . $folio;
		    $st = DB::delete($query);
		    
		    
		    $query = "DELETE FROM circulo_cuentas WHERE folioconsultaotorgante = " . $folio;
		    $st = DB::delete($query);
		    
		    
		    $query = "DELETE FROM circulo_empleos WHERE folioconsultaotorgante = " . $folio;
		    $st = DB::delete($query);
		    
		    
		    $query = "DELETE FROM circulo_consultas_efectuadas WHERE folioconsultaotorgante = " . $folio;
		    $st = DB::delete($query);
		    
		    
		    // Exclusivas PERSONA MORAL
			$query = "DELETE FROM buro_consultapm_hd WHERE folio_consulta = " . $folio;
			$st = DB::delte($query);
			

			$query = "DELETE FROM buro_consultapm_em WHERE folio_consulta = " . $folio;
			$st = DB::delete($query);
			

			$query = "DELETE FROM buro_consultapm_hc WHERE folio_consulta = " . $folio;
			$st = DB::delete($query);
			

			$query = "DELETE FROM buro_consultapm_hr WHERE folio_consulta = " . $folio;
			$st = DB::delete($query);

			$query = "DELETE FROM buro_consultapm_dc WHERE folio_consulta = " . $folio;
			$st = DB::delete($query);
			

			$query = "DELETE FROM buro_consultapm_ac WHERE folio_consulta = " . $folio;
			$st = DB::delete($query);
			

			$query = "DELETE FROM buro_consultapm_cr WHERE folio_consulta = " . $folio;
			$st = DB::delete($query); 
			

			$query = "DELETE FROM buro_consultapm_hi WHERE folio_consulta = " . $folio;
			$st = DB::delete($query);
			

			$query = "DELETE FROM buro_consultapm_co WHERE folio_consulta = " . $folio;
			$st = DB::delete($query);
			

			$query = "DELETE FROM buro_consultapm_fd WHERE folio_consulta = " . $folio;
			$st = DB::delte($query);
			

			$query = "DELETE FROM buro_consultapm_cn WHERE folio_consulta = " . $folio;
			$st = DB::delte($query);
			

			$query = "DELETE FROM buro_consultapm_er WHERE folio_consulta = " . $folio;
			$st = DB::delte($query);

			$query = "DELETE FROM buro_consultapm_ci WHERE folio_consulta = " . $folio;
			$st = DB::delte($query);

			return true;
		} catch (\Exception $e) {
			$this->idError = -4;
			$this->descError = "Error al limpiar el folio de consulta - " . $e->getMessage();
			return false;
		}
	}
	
	// Anexo 1
	//@SuppressWarnings("unused")
	private function getDescripcionTipoUsuario($codigo) {
		if ("001" == $codigo)
			return "Banco";
		if ("002" == $codigo)
			return "Arrendadora";
		if ("003" == $codigo)
			return "Uni�n de Cr�dito";
		if ("004" == $codigo)
			return "Factoraje";
		if ("005" == $codigo)
			return "Otras Financieras";
		if ("007" == $codigo)
			return "Almacenadoras";
		if ("008" == $codigo)
			return "Fondos y Fideicomisos";
		if ("009" == $codigo)
			return "Seguros";
		if ("010" == $codigo)
			return "Fianzas";
		if ("011 " == $codigo)
			return "Caja de Ahorro";
		if ("012" == $codigo)
			return "Gobierno";
		if ("013" == $codigo)
			return "Administradora de Cartera";
		if ("014" == $codigo)
			return "Sociedad de Informaci�n Crediticia";
		if ("015" == $codigo)
			return "Comunicaciones";
		if ("016" == $codigo)
			return "Servicios";
		if ("999" == $codigo)
			return "Comercial";
		return "";
	}
	
	// Anexo 2
	private function getTipoCredito($value, $nombreGenerico) {
		if ("1300" == $value)
			return ((! $nombreGenerico) ? "Cartera de Arrendamiento Puro y Cr�ditos " : "ARREN PURO");
		if ("1301" == $value)
			return ((! $nombreGenerico) ? "Descuentos " : "DESCUENTOS");
		if ("1302" == $value)
			return ((! $nombreGenerico) ? "Quirografario " : "QUIROG");
		if ("1303" == $value)
			return ((! $nombreGenerico) ? "Con Colateral " : "COLATERAL");
		if ("1304" == $value)
			return ((! $nombreGenerico) ? "Prendario " : "PRENDAR");
		if ("1305" == $value)
			return ((! $nombreGenerico) ? "Cr�ditos simples y cr�ditos en cuenta corriente " : "SIMPLE");
		if ("1306" == $value)
			return ((! $nombreGenerico) ? "Pr�stamos con garant�a de unidades industriales " : "P.G.U.I.");
		if ("1307" == $value)
			return ((! $nombreGenerico) ? "Cr�ditos de habilitaci�n o av�o " : "HABILITACION");
		if ("1308" == $value)
			return ((! $nombreGenerico) ? "Cr�ditos Refaccionarios " : "REFACC");
		if ("1309" == $value)
			return ((! $nombreGenerico) ? "Prestamos Inmobil Emp Prod de Bienes o Servicios " : "I.E.P.B.S.");
		if ("1310" == $value)
			return ((! $nombreGenerico) ? "Pr�stamos para la vivienda " : "VIVIENDA");
		if ("1311" == $value)
			return ((! $nombreGenerico) ? "Otros cr�ditos con garant�a inmobiliaria " : "O.C. GARANTIA INMOB");
		if ("1314" == $value)
			return ((! $nombreGenerico) ? "No Disponible " : "NO DISPONIBLE");
		if ("1316" == $value)
			return ((! $nombreGenerico) ? "Otros adeudos vencidos " : "O.A.V.");
		if ("1317" == $value)
			return ((! $nombreGenerico) ? "Cr�ditos venidos a menos aseg. Gtias. Adicionales " : "C.V.A.");
		if ("1320" == $value)
			return ((! $nombreGenerico) ? "Cartera de Arrendamiento Financiero Vigente " : "ARREN VIGENTE");
		if ("1321" == $value)
			return ((! $nombreGenerico) ? "Cartera de Arrendamiento Financiero Sindicado con Aportaci�n " : "ARREN SINDICADO");
		if ("1322" == $value)
			return ((! $nombreGenerico) ? "Cr�dito de Arrendamiento " : "ARREND");
		if ("1323" == $value)
			return ((! $nombreGenerico) ? "Cr�ditos Reestructurados " : "REESTRUCTURADOS");
		if ("1324" == $value)
			return ((! $nombreGenerico) ? "Cr�ditos Renovados " : "RENOVADOS");
		if ("1327" == $value)
			return ((! $nombreGenerico) ? "Arrendamiento Financiero Sindicado " : "ARR. FINAN. SINDICADO");
		if ("1340" == $value)
			return ((! $nombreGenerico) ? "Cartera descontada con Inst. de Cr�dito " : "REDESCUENTO");
		if ("1341" == $value)
			return ((! $nombreGenerico) ? "Redescuento otra cartera descontada " : "O. REDESCUENTO");
		if ("1342" == $value)
			return ((! $nombreGenerico) ? "Redescuento, cartera de cr�dito reestructurado mediante su descuento en programas Fidec. " : "RED. REESTRUCTURADOS");
		if ("1350" == $value)
			return ((! $nombreGenerico) ? "Prestamos con Fideicomisos de Garant�a " : "PRESTAMOS C/FIDEICOMISOS GARANT�A");
		if ("1380" == $value)
			return ((! $nombreGenerico) ? "Tarjeta de Cr�dito empresarial / Tarjeta Corporativa " : "T. CRED. EMPRESARIAL-CORPORATIVA");
		if ("2303" == $value)
			return ((! $nombreGenerico) ? "Cartas de Cr�dito " : "CARTAS DE CREDITO");
		if ("3011" == $value)
			return ((! $nombreGenerico) ? "Cartera de Factoraje con Recursos " : "FACTORAJE C/REC");
		if ("3012" == $value)
			return ((! $nombreGenerico) ? "Cartera de Factoraje sin Recursos " : "FACTORAJE S/REC");
		if ("3230" == $value)
			return ((! $nombreGenerico) ? "Anticipo a Clientes Por Promesa de Factoraje " : "ANT.A.C.P.P.FACTORAJE");
		if ("3231" == $value)
			return ((! $nombreGenerico) ? "Cartera de Arrendamiento Financiero Vigente " : "ARREN VIGENTE");
		if ("6103" == $value)
			return ((! $nombreGenerico) ? "Adeudos por Aval " : "ADEUDOS POR AVAL");
		if ("6105" == $value)
			return ((! $nombreGenerico) ? "Cartas de Cr�ditos No Dispuestas " : "CARTAS DE CR�DITOS NO DISPUESTAS");
		if ("6228" == $value)
			return ((! $nombreGenerico) ? "Fideicomisos Programa de apoyo crediticio a la planta productiva Nacional en Udis " : "FIDEICOMISOS PLANTA PRODUCTIVA");
		if ("6229" == $value)
			return ((! $nombreGenerico) ? "Fideicomisos Programa de apoyo crediticio a los Estados y Municipios UDIS " : "FIDEICOMISOS EDOS");
		if ("6230" == $value)
			return ((! $nombreGenerico) ? "Fideicomisos Programa de apoyo para deudores de cr�ditos de Vivienda UDIS " : "FIDEICOMISOS VIVIENDA");
		if ("6240" == $value)
			return ((! $nombreGenerico) ? "Aba Pasem II " : "ABA PASEM II");
		if ("6250" == $value)
			return ((! $nombreGenerico) ? "Tarjeta de Servicio " : "TARJETA DE SERVICIO");
		if ("6260" == $value)
			return ((! $nombreGenerico) ? "Cr�dito Fiscal " : "CR�DITO FISCAL");
		if ("6270" == $value)
			return ((! $nombreGenerico) ? "Cr�dito Automotriz " : "CR�DITO AUTOMOTRIZ");
		if ("6280" == $value)
			return ((! $nombreGenerico) ? "L�nea de Cr�dito " : "L�NEA DE CR�DITO");
		if ("6290" == $value)
			return ((! $nombreGenerico) ? "Seguros " : "SEGUROS");
		if ("6291" == $value)
			return ((! $nombreGenerico) ? "Fianzas " : "FIANZAS");
		if ("6292" == $value)
			return ((! $nombreGenerico) ? "Fondos y Fideicomisos " : "FONDOS Y FIDEICOMISOS");
		
		return "";
	}
	
	// Anexo 4
	// @SuppressWarnings("unused")
	private function getNombreClaveObservacion($clave) {
		return "";
	}
	
	// Anexo 6
	// @SuppressWarnings("unused")
	private function getDescripcionClavePrevencion($clave) {
		if ("78" == $clave)
			return "Negocio receptor de tarjetas de cr�dito que ocasion� p�rdida al Usuario";
		if ("79" == $clave)
			return "Persona relacionada con la empresa o con Persona F�sica con Actividad Empresarial con clave de prevenci�n";
		if ("80" == $clave)
			return "Cliente declarado en quiebra, suspensi�n de pagos o en concurso mercantil";
		if ("81" == $clave)
			return "Cliente en tr�mite judicial";
		if ("82" == $clave)
			return "Cliente que propici� p�rdida al Otorgante por fraude comprobado, declarado conforme a sentencia judicial";
		if ("83" == $clave)
			return "Cliente que solicit� y/o acord� con el Otorgante liquidaci�n del cr�dito con pago menor a la deuda total";
		if ("84" == $clave)
			return "El Usuario no ha podido localizar al Cliente, titular de la cuenta";
		if ("85" == $clave)
			return "Cliente desvi� recursos a fines distintos a los pactados, debidamente comprobado";
		if ("86" == $clave)
			return "Cliente que dispuso de las garant�as que respaldan el cr�dito sin autorizaci�n del Otorgante";
		if ("87" == $clave)
			return "Cliente que enajena o cambia r�gimen de propiedad de sus bienes o permite grav�menes sobre los mismos";
		if ("88" == $clave)
			return "Cliente que dispuso de las retenciones de sus trabajadores, no enterando a la Instituci�n correspondiente";
		if ("92" == $clave)
			return "Cliente que propici� p�rdida total al Otorgante";
		
		return "";
	}
	
	// Anexo 7
	// @SuppressWarnings("unused")
	private function getDescripcionHistoricoPagos($codigo) {
		if ("D" == $codigo)
			return "Informaci�n anulada a solicitud del Usuario";
		if ("-" == $codigo)
			return "Per�odo no reportado por el Usuario";
		if ("1" == $codigo)
			return "Cuenta al corriente, 0 d�as de atraso de su fecha l�mite de pago";
		if ("2" == $codigo)
			return "Cuenta con atraso de 1 a 29 d�as de su fecha l�mite de pago";
		if ("3" == $codigo)
			return "Cuenta con atraso de 30 a 59 d�as de su fecha l�mite de pago";
		if ("4" == $codigo)
			return "Cuenta con atraso de 60 a 89 d�as de su fecha l�mite de pago";
		if ("5" == $codigo)
			return "Cuenta con atraso de 90 a 119 d�as de su fecha l�mite de pago";
		if ("6" == $codigo)
			return "Cuenta con atraso de 120 a 179 d�as de su fecha l�mite de pago";
		if ("7" == $codigo)
			return "Cuenta con atraso de 180 d�as o m�s de su fecha l�mite de pago";
		if (" " == $codigo)
			return "Periodo eliminado por el Usuario en raz�n de aplicaci�n de la Ley para Regular a las Sociedad de Informaci�n Crediticia";

		return "";
	}
	
	// Anexo 8 
	// @SuppressWarnings("unused")
	private function getDescripcionMoneda($codigo) {
		return "";
	}
	
	// Anexo 9
	// @SuppressWarnings("unused")
	private  function getDescripcionPaises($codigo) {
		return "";
	}
	
	// Anexo 10
	// @SuppressWarnings("unused")
	private  function getDescripcionCodigoEstados($codigo) {
		return "";
	}
	
	// Anexo 11
	private function getNombreClaveCalifica($claveCalifica) {
		if ("0" == $claveCalifica)
			return "BK12_CLEAN";
		if ("1" == $claveCalifica)
			return "BK12_NUM_CRED";
		if ("2" == $claveCalifica)
			return "BK12_NUM_TC_ACT";
		if ("3" == $claveCalifica)
			return "NBK12_NUM_CRED";
		if ("4" == $claveCalifica)
			return "BK12_NUM_EXP_PAIDONTIME";
		if ("5" == $claveCalifica)
			return "BK12_PCT_PROMT";
		if ("6" == $claveCalifica)
			return "NBK12_PCT_PROMT";
		if ("7" == $claveCalifica)
			return "BK12_PCT_SAT";
		if ("8" == $claveCalifica)
			return "NBK12_PCT_SAT";
		if ("9" == $claveCalifica)
			return "BK24_PCT_60PLUS";
		if ("10" == $claveCalifica)
			return "NBK24_PCT_60PLUS";
		if ("11" == $claveCalifica)
			return "NBK12_COMM_PCT_PLUS";
		if ("12" == $claveCalifica)
			return "BK12_PCT_90PLUS";
		if ("13" == $claveCalifica)
			return "BK12_DPD_PROM";
		if ("14" == $claveCalifica)
			return "BK12_IND_QCRA";
		if ("15" == $claveCalifica)
			return "BK12_MAX_CREDIT_AMT";
		if ("16" == $claveCalifica)
			return "MONTHS_ON_FILE_BANKING";
		if ("17" == $claveCalifica)
			return "MONTHS_SINCE_LAST_OPEN_BANKING";
		if ("18" == $claveCalifica)
			return "BK_IND_PMOR";
		if ("19" == $claveCalifica)
			return "BK24_IND_EXP";
		if ("20" == $claveCalifica)
			return "12_INST";
		if ("21" == $claveCalifica)
			return "BK_DEUDA_TOT";
		if ("22" == $claveCalifica)
			return "BK_DEUDA_CP";
		if ("23" == $claveCalifica)
			return "NBK_DEUDA_TOT";
		if ("24" == $claveCalifica)
			return "NBK_DEUDA_CP";
		if ("25" == $claveCalifica)
			return "DEUDA_TOT";
		if ("26" == $claveCalifica)
			return "DEUDA_TOT_CP";
		
		return "";
	}


	//HISTORIAL DE MODIFICACIONES
	//---------------------------------------------------------------
	//VERSI�N |   FECHA    |     AUTOR      | DESCRIPCI�N
	//---------------------------------------------------------------
	//   2.1  | 30/09/2016 | Miguel Aguilar | Se agreg� un replace a la cadena de consulta para que cambie las letras � por N.
	//   2.1  | 03/10/2016 | Miguel Aguilar | Se cambi� el valor de la etiqueta que contiene el valor correspondiente a la firma de autorizaci�n del cliente.
	//   2.1  | 26/10/2016 | Miguel Aguilar | Se agreg� una forma de interpretar los errores que son devueltos como respuesta desde buro.
}
