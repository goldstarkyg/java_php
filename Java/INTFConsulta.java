package net.sistacc.ws.buro;

import java.text.DecimalFormat;
import java.util.logging.Logger;
import com.google.gson.Gson;

public class INTFConsulta {
	
	public static void main(String[] args) {
		String prueba ="";
		prueba = generaINTF("{tarjetaTiene: \"V\", tarjetaNum: \"6856\", hipotecarioTiene: \"F\", automotrizTiene: \"F\", ciudad: \" rfc: \"ROCG750601\", referencia: \"GRALCERTIF66\", tipoCuenta: \"F\", cveMoneda: \"MX\", numFirma: 12345, mtoContrato: 90000, aPaterno: \"ROMERO\", aMaterno: \"CUENTO\", nombre: \"GILBERTO\", fecNacimiento: \"01061975\", domicilio: \"GIRASOLES 32 INT 3\", colonia: \"SAN FRANCISCO\", municipio: \"TLALPAN\", estado: \"DF\", cp:\"10810\"}");
		//"{tarjetaTiene: \"F\", hipotecarioTiene: \"F\", automotrizTiene: \"F\", rfc: \"MELA740205\", referencia: \"1234567980\", tipoCuenta: \"F\", cveMoneda: \"MX\", numFirma: 12345, mtoContrato: 90000, aPaterno: \"MEZA\", aMaterno: \"LIZARDI\", nombre: \"AUGUSTO\", fecNacimiento: \"05021974\", domicilio: \"AVE DELFINES SN\", colonia: \"fidepaz\", municipio: \"La pAz\", estado: \"bcs\", cp:\"23090\", telefono: \"1236250\"}") ;
		//"{rfc: \"SEGP7608103ZG\", referencia: \"1234567980\", tipoCuenta: \"F\", cveMoneda: \"MX\", numFirma: 12345, aPaterno: \"Sermeno\", aMaterno: \"Gochez\", nombre: \"patricia\", fecNacimiento: \"10081976\", domicilio: \"croto 119\", colonia: \"jardines del sur\", municipio: \"La pAz\", estado: \"bcs\", cp:\"23090\", telefono: \"1211389\"}");
		System.out.println(prueba);
	}
	
	public static String generaINTF(String valores) {
    	String intf="";
    	String error = "";
    	try {
    		String tmp = "";
            // obtener valores
        	Gson gson = new Gson();
			Valores v = gson.fromJson(valores,Valores.class);
    		//intf = encabezado("MC27340001","ASP FINANCIERA",v);
			intf = encabezado("ZM27341006","OAnuXfw3",v);
    		if (intf.contains("ERROR"))
    			return intf;
    		intf += nombre(v);
    		if (intf.contains("ERROR"))
    			return intf;
    		intf += domicilio(v);
    		if (intf.contains("ERROR"))
    			return intf;
    		intf += autenticacion(v);
    		if (intf.contains("ERROR"))
    			return intf;
    		intf += fin(intf);
    	} catch(Exception e) {
    		error = e.toString();
    		Logger.getAnonymousLogger().info(error);
    	} finally {
    		/*try { m_statement2.close();m_statement.close(); }
    		catch(Exception e) {}*/
    	}
    	//System.out.println("encabezado: " + intf);
		return intf;		
	}

	public static String autenticacion(Valores val) {
		String aut = "";
		try {
			aut = "AU03RCN000110125" + val.getReferencia();
			String sReferencia = "                         ";
			// forma la referencia a 25 espacios:
				//sReferencia = sReferencia.substring(0,sReferencia.length() - val.getReferencia().length());
				//sReferencia += val.getReferencia();
			aut = "AU";
			aut += "03RCN";
			aut += "00011";
			aut += "0125" + sReferencia;
			//TIene tarjeta
			if (val.getTarjetaTiene() == null)
				return "ERROR: Falta tiene tarjeta credito";
			if (val.getTarjetaTiene().length() != 1)
				return "ERROR: Longitud tiene tarjeta credito";
			if ((val.getTarjetaTiene().compareTo("V")!=0) && (val.getTarjetaTiene().compareTo("F")!=0))
				return "ERROR: Valor tiene tarjeta credito";
			aut += "0201" + val.getTarjetaTiene();
			//Numero tarjeta
			if (val.getTarjetaTiene().compareTo("V")==0) {
				if ((val.getTarjetaNum() == null) || (val.getTarjetaNum().compareTo("")==0))
					return "ERROR: Falta num tarjeta credito";
				if (val.getTarjetaNum().length() != 4)
					return "ERROR: Longitud num tarejta credito";
				aut += "0404" + val.getTarjetaNum();
			}
			//Credito hipotecario
			if (val.getHipotecarioTiene() == null)
				return "ERROR: Falta credito hipotecario";
			if (val.getHipotecarioTiene().length() != 1)
				return "ERROR: Longitud credito hipotecario";
			if ((val.getHipotecarioTiene().compareTo("V")!=0) && (val.getHipotecarioTiene().compareTo("F")!=0))
				return "ERROR: Valor tiene credito hipotecario";
			aut += "0701" + val.getHipotecarioTiene();
			//credito automotriz
			if (val.getAutomotrizTiene() == null)
				return "ERROR: Falta credito automotriz";
			if (val.getAutomotrizTiene().length() != 1)
				return "ERROR: Longitud credito automotriz";
			if ((val.getAutomotrizTiene().compareTo("V")!=0) && (val.getAutomotrizTiene().compareTo("F")!=0))
				return "ERROR: Valor tiene credito automotriz";
			aut += "1101" + val.getAutomotrizTiene();
		} catch(Exception e) {
    		Logger.getAnonymousLogger().info(e.toString());
    	} finally {
    	}
		return aut;
	}
	
	public static String encabezado(String clave, String pass, Valores val) {
		String enc = "";
		try {
			// validaciones
			//if ((val.getReferencia() == null) || (val.getReferencia().compareTo("") == 0)) 
				//return "ERROR: Falta referencia";
			if (val.getMtoContrato() == 0) 
				return "ERROR: Falta monto";

			String sMonto = "000000000";
    		DecimalFormat df_importe  = new DecimalFormat("#######");
			String sReferencia = "                         ";
			enc = "INTL" ;
			enc += "11";
			// forma la referencia a 25 espacios:
				//sReferencia = sReferencia.substring(0,sReferencia.length() - val.getReferencia().length());
				//sReferencia += val.getReferencia();
			enc += sReferencia; 	//Referencia 25 espacios
			enc += "001"; 	//Producto
			enc += "MX"; 	//Pais
			enc += "0000";	//Identificador
			enc += clave; 	//Clave
			enc += pass; 	//Password
			if ((val.getResponsabilidad() == null) || (val.getResponsabilidad().compareTo("") == 0))
				enc += "I"; 	//Indicador
			else
				enc += val.getResponsabilidad();
			if ((val.getContrato() == null) || (val.getContrato().compareTo("") == 0))
				enc += "CL"; 	//Contrato valo original LC cambio a CL
			else
				enc += val.getContrato();
			enc += "MX"; 	//Pesos
			/*sMonto = sMonto + String.valueOf(monto2);//;monto;
			monto = sMonto.length();*/
			String mto=String.valueOf(df_importe.format(val.getMtoContrato()));
			//mto= df_importe.format(mto);
			sMonto = sMonto.substring(0,sMonto.length() - mto.length());
			sMonto = sMonto + mto;
			
			enc += sMonto; 	//Monto
			enc += "SP"; 	//Idioma
			enc += "01"; 	//Tipo Salida
			enc += " "; 	//Tamañ				
			enc += "    "; 	//Impresora
			enc += "0000000"; 	//Pesos
		} catch(Exception e) {
    		Logger.getAnonymousLogger().info(e.toString());
    	} finally {
    	}
		return enc;
	}

	public static String nombre(Valores val) {
		String nom = "";
		try { //PN06MENDEZ0008GONZALEZ0205MARIO0408030519510513MEGA510503RE3
			//validaciones
			if ((val.getaPaterno() == null) || (val.getaPaterno().compareTo("") == 0)) 
				return "ERROR: Falta apellido paterno";
			if ((val.getNombre() == null) || (val.getNombre().compareTo("") == 0)) 
				return "ERROR: Falta nombre propio";
			if ((val.getFecNacimiento() == null) || (val.getFecNacimiento().compareTo("") == 0)) 
				return "ERROR: Falta fecha nacimiento";
			if ((val.getFecNacimiento().length() != 8)) 
				return "ERROR: longitud erronea en la fecha de nacimiento";
			if ((val.getRfc() == null) || (val.getRfc().compareTo("") == 0)) 
				return "ERROR: Falta rfc";
			if ((val.getRfc().length() != 10) && (val.getRfc().length() != 13)) 
				return "ERROR: longitud erronea en el rfc";
			
			String vLen = "";
			vLen = (val.getaPaterno().length()<10 ? "0"+val.getaPaterno().length() : ""+val.getaPaterno().length()); 
			nom ="PN" + vLen + val.getaPaterno().toUpperCase(); 
			if ((val.getaMaterno() == null) || (val.getaMaterno().compareTo("") == 0)) {
				nom += "0016NO PROPORCIONADO";
			} else {
				vLen = (val.getaMaterno().length()<10 ? "0"+val.getaMaterno().length() : ""+val.getaMaterno().length()); 
				nom += "00" + vLen + val.getaMaterno().toUpperCase(); }
			vLen = (val.getNombre().length()<10 ? "0"+val.getNombre().length() : ""+val.getNombre().length()); 
			nom += "02" + vLen + val.getNombre().toUpperCase();
			nom += "0408" + val.getFecNacimiento();
			vLen = (val.getRfc().length()<10 ? "0"+val.getRfc().length() : ""+val.getRfc().length());
			nom += "05" + vLen + val.getRfc().toUpperCase();
		} catch(Exception e) {
    		Logger.getAnonymousLogger().info(e.toString());
    	} finally {
    	}
		return nom;
	}

	public static String domicilio(Valores val) {
		String dom = "";
		try {
			//validaciones
			if ((val.getDomicilio() == null) || (val.getDomicilio().compareTo("") == 0)) 
				return "ERROR: Falta domicilio";
			//if ((val.getColonia() == null) || (val.getColonia().compareTo("") == 0)) 
				//return "ERROR: Falta colonia";
			//if ((val.getMunicipio() == null) || (val.getMunicipio().compareTo("") == 0)) 
				//return "ERROR: Falta municipio";
			if (((val.getMunicipio() == null) || (val.getMunicipio().compareTo("") == 0)) && ((val.getColonia() == null) || (val.getColonia().compareTo("") == 0)) && ((val.getCiudad() == null) || (val.getCiudad().compareTo("") == 0)))
				return "ERROR: Falta colonia, municipio y ciudad";
			if ((val.getEstado() == null) || (val.getEstado().compareTo("") == 0)) 
				return "ERROR: Falta estado";
			if ((val.getCp() == null) || (val.getCp().compareTo("") == 0)) 
				return "ERROR: Falta codigo postal";
			
			String vLen = "";
			vLen = (val.getDomicilio().length()<10 ? "0"+val.getDomicilio().length() : ""+val.getDomicilio().length()); 
			dom = "PA" + vLen + val.getDomicilio().toUpperCase();
			vLen = (val.getColonia().length()<10 ? "0"+val.getColonia().length() : ""+val.getColonia().length()); 
			dom += "01" + vLen + val.getColonia().toUpperCase();
			vLen = (val.getMunicipio().length()<10 ? "0"+val.getMunicipio().length() : ""+val.getMunicipio().length()); 
			dom += "02" + vLen + val.getMunicipio().toUpperCase();
			vLen = (val.getEstado().length()<10 ? "0"+val.getEstado().length() : ""+val.getEstado().length()); 
			dom += "04" + vLen + val.getEstado().toUpperCase();
			vLen = (val.getCp().length()<10 ? "0"+val.getCp().length() : ""+val.getCp().length()); 
			dom += "05" + vLen + val.getCp();
			
			//"PA10DURANGO 320111LOMAS ALTAS0207TLALPAN0306MEXICO0402DF050514210060804062000";
		} catch(Exception e) {
    		Logger.getAnonymousLogger().info(e.toString());
    	} finally {
    	}
		return dom;
	}

	private static String fin(String sResultado) {
		String sFin = "";
		int longitud = 0;
		longitud = sResultado.length();
		if (longitud > 0) {
			longitud = longitud + 15;
			sFin = "00000" + longitud;
			longitud = sFin.length();
			sFin = sFin.substring(longitud-5,longitud);
			sFin = "ES05" + sFin + "0002**";
		}
		return sFin;
	}	
}
