<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;

use DB;
use Response;
use Log;


class INTFConsultaController extends Controller
{

    public  function main($args) {
            $prueba ="";
            $prueba = $this->generaINTF("{tarjetaTiene: \"V\", tarjetaNum: \"6856\", hipotecarioTiene: \"F\", automotrizTiene: \"F\", ciudad: \" rfc: \"ROCG750601\", referencia: \"GRALCERTIF66\", tipoCuenta: \"F\", cveMoneda: \"MX\", numFirma: 12345, mtoContrato: 90000, aPaterno: \"ROMERO\", aMaterno: \"CUENTO\", nombre: \"GILBERTO\", fecNacimiento: \"01061975\", domicilio: \"GIRASOLES 32 INT 3\", colonia: \"SAN FRANCISCO\", municipio: \"TLALPAN\", estado: \"DF\", cp:\"10810\"}");

    }

    public  function generaINTF($valores) {
        $intf="";
    	$error = "";
    	try {
            $tmp = "";
            // obtener valores
        	
			$v = json_decode($valores);
    		
			$intf = $this->encabezado("ZM27341006","OAnuXfw3",$v);
    		if (strpos($intf,"ERROR") !== false)
                return $intf;
    		$intf .= $this->nombre($v);
    		if (strpos($intf, "ERROR") !== false)
                return $intf;
    		$intf .= $this->domicilio($v);
    		if (strpos($intf , "ERROR") !== false)
                return $intf;
    		$intf .= $this->autenticacion($v);
    		if (strpos($intf , "ERROR") !== false)
                return $intf;
    		$intf .= $this->fin($intf);
    	} catch(\Exception $e) {
        	$error = $e;
        	Log::info($error);
         }

		return $intf;
	}

	public static function autenticacion($val) {
        $aut = "";
		try {
            $aut = "AU03RCN000110125" . $val->getReferencia();
            $sReferencia = "                         ";
			
			$aut = "AU";
			$aut .= "03RCN";
			$aut .= "00011";
			$aut .= "0125" . $sReferencia;
			//TIene tarjeta
			if ($val->getTarjetaTiene() == null)
                return "ERROR: Falta tiene tarjeta credito";
			if (strlen($val->getTarjetaTiene()) != 1)
                return "ERROR: Longitud tiene tarjeta credito";
			if ((strcmp($val->getTarjetaTiene(), "V") != 0)  && (strcmp($val->getTarjetaTiene() , "F") != 0))
                return "ERROR: Valor tiene tarjeta credito";
			$aut .= "0201" . $val->getTarjetaTiene();
			//Numero tarjeta
			if ( strcmp($val->getTarjetaTiene() , "V") == 0 ) {
                if (($val->getTarjetaNum() == null) || (strcmp($val->getTarjetaNum() , "") == 0 ))
                    return "ERROR: Falta num tarjeta credito";
                if (strlen($val->getTarjetaNum()) != 4)
                    return "ERROR: Longitud num tarejta credito";
                $aut .= "0404" . $val->getTarjetaNum();
            }
			//Credito hipotecario
			if ($val->getHipotecarioTiene() == null)
                return "ERROR: Falta credito hipotecario";
			if (strlen($val->getHipotecarioTiene()) != 1)
                return "ERROR: Longitud credito hipotecario";
			if (( strcmp($val->getHipotecarioTiene() , "V" ) != 0 ) && (strcmp($val->getHipotecarioTiene() , "F") !=0 ))
                return "ERROR: Valor tiene credito hipotecario";
			$aut .= "0701" . $val->getHipotecarioTiene();
			//credito automotriz
			if ($val->getAutomotrizTiene() == null)
                return "ERROR: Falta credito automotriz";
			if (strlen($val->getAutomotrizTiene()) != 1)
                return "ERROR: Longitud credito automotriz";
			if ((strcmp($val->getAutomotrizTiene() , "V") != 0) && (strcmp($val->getAutomotrizTiene() , "F") !=0 ))
                return "ERROR: Valor tiene credito automotriz";
			$aut .= "1101" . $val->getAutomotrizTiene();
		} catch(\Exception $e) {
           Log::info($e);
        } 
		return $aut;
	}


	public static function encabezado($clave, $pass, $val) {
        $enc = "";
		try {         
            if ($val->getMtoContrato() == 0)
                return "ERROR: Falta monto";

            $sMonto = "000000000";
    		$df_importe  = "#######";
			$sReferencia = "                         ";
			$enc = "INTL" ;
			$enc .= "11";
			
			$enc .= $sReferencia; 	//Referencia 25 espacios
			$enc .= "001"; 	//Producto
			$enc .= "MX"; 	//Pais
			$enc .= "0000";	//Identificador
			$enc .= $clave; 	//Clave
			$enc .= $pass; 	//Password
			if (($val->getResponsabilidad() == null) || (strcmp($val->getResponsabilidad(), "") == 0))
                $enc .= "I"; 	//Indicador
            else
                $enc .= $val->getResponsabilidad();
			if (($val->getContrato() == null) || (strcmp($val.getContrato() , "") == 0))
                $enc .= "CL"; 	//Contrato valo original LC cambio a CL
            else
                $enc .= $val->getContrato();
			$enc .= "MX"; 	//Pesos
			
			$mto= sprintf('%07d', $val->getMtoContrato());
			
			$sMonto = substr($sMonto , 0 , strlen($sMonto) - strlen($mto));
			$sMonto = $sMonto . $mto;

			$enc .= $sMonto; 	//Monto
			$enc .= "SP"; 	//Idioma
			$enc .= "01"; 	//Tipo Salida
			$enc .= " "; 	//Tamañ
			$enc .= "    "; 	//Impresora
			$enc .= "0000000"; 	//Pesos
		} catch(\Exception $e) {
          Log::info($e);
        } 
		return $enc;
	}

    /*
    *nombre
    */    
	public static function nombre($val) {
        $nom = "";
		try { 
            if (($val->getaPaterno() == null) || (strcmp($val->getaPaterno() , "") == 0))
                return "ERROR: Falta apellido paterno";
            if (($val->getNombre() == null) || (strcmp($val->getNombre() , "") == 0))
                return "ERROR: Falta nombre propio";
            if (($val->getFecNacimiento() == null) || (strcmp($val->getFecNacimiento() , "") == 0))
                return "ERROR: Falta fecha nacimiento";
            if ((strlen($val->getFecNacimiento()) != 8))
                return "ERROR: longitud erronea en la fecha de nacimiento";
            if (($val->getRfc() == null) || (strcmp($val->getRfc() , "") == 0))
                return "ERROR: Falta rfc";
            if ((strlen($val->getRfc()) != 10) && (strlen($val->getRfc()) != 13))
                return "ERROR: longitud erronea en el rfc";

            $vLen = "";
			$vLen = strlen($val->getaPaterno()) < 10 ? "0" . strlen($val->getaPaterno()) : "" . strlen($val->getaPaterno());
			$nom ="PN" . $vLen . toUpperCase($val->getaPaterno());
			if (($val->getaMaterno() == null) || (strcmp($val->getaMaterno() , "") == 0)) {
                $nom .= "0016NO PROPORCIONADO";
            } else {
                $vLen = strlen($val->getaMaterno()) < 10 ? "0" . strlen($val->getaMaterno()) : "" . strlen($val->getaMaterno());
                $nom .= "00" . $vLen . toUpperCase($val->getaMaterno());
            }
			$vLen = strlen($val->getNombre()) < 10 ? "0" .  strlen($val->getNombre()) : "" . strlen($val->getNombre());
			$nom .= "02" . $vLen . toUpperCase($val->getNombre());
			$nom .= "0408" . $val->getFecNacimiento();
			$vLen = strlen($val->getRfc()) < 10 ? "0" . strlen($val->getRfc()) : "" . strlen($val->getRfc());
			$nom .= "05" . $vLen . toUpperCase($val->getRfc());
		} catch(\Exception $e) {
          Log::info($e);
        } 
		return $nom;
	}

    /*
    *domicilio
    */    
	public static function domicilio($val) {
        $dom = "";
		try {
            //validaciones
            if (($val->getDomicilio() == null) || (strcmp($val->getDomicilio() , "") == 0))
                return "ERROR: Falta domicilio";
            
            if (($val->getMunicipio() == null) || (strcmp($val->getMunicipio()) == 0) && ($val->getColonia() == null) || (strcmp($val->getColonia() , "") == 0) && ($val->getCiudad() == null) || (strcmnp($val->getCiudad() , "") == 0))
                return "ERROR: Falta colonia, municipio y ciudad";
            if (($val->getEstado() == null) || (strcmp($val->getEstado() , "") == 0))
                return "ERROR: Falta estado";
            if (($val->getCp() == null) || (strcmp($val.getCp() , "") == 0))
                return "ERROR: Falta codigo postal";

            $vLen = "";
			$vLen = strlen($val->getDomicilio()) < 10 ? "0" . strlen($val->getDomicilio()) : "" . strlen($val->getDomicilio);
			$dom = "PA" . $vLen . toUpperCase($val.getDomicilio());
			$vLen = strlen($val.getColonia()) < 10 ? "0" . strlen($val->getColonia()) : "" . strlen($val->getColonia());
			$dom .= "01" . $vLen . toUpperCase($val->getColonia());
			$vLen = strlen($val->getMunicipio()) < 10 ? "0" . strlen($val->getMunicipio()) : "" . strlen($val->getMunicipio());
			$dom .= "02" . $vLen . toUpperCase($val->getMunicipio());
			$vLen = strlen($val.getEstado()) < 10 ? "0" . strlen($val->getEstado()) : "" . strlen($val->getEstado());
			$dom .= "04" . $vLen . toUpperCase($val.getEstado());
			$vLen = strlen($val->getCp()) < 10 ? "0" . strlen($val->getCp()) : "" . strlen($val->getCp());
			$dom .= "05" . $vLen . $val->getCp();

			
		} catch(\Exception $e) {
          Log::info($e);
        } 
		return $dom;
	}

    /*
    *fin
    */    
	private static function fin($sResultado) {
        $sFin = "";
		$longitud = 0;
		$longitud = strlen($sResultado);
		if ($longitud > 0) {
            $longitud = $longitud + 15;
            $sFin = "00000" . $longitud;
            $longitud = strlen($sFin);
            $sFin = substr($sFin , $longitud-5 , $longitud);
            $sFin = "ES05" . $sFin . "0002**";
        }
		return $sFin;
	}
    
}
