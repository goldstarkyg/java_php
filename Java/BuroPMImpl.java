package net.sistacc.ws.buro;

import java.io.DataInputStream;
import java.io.DataOutputStream;
import java.net.Socket;
import java.sql.Connection;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.sql.Statement;
import java.text.SimpleDateFormat;
import java.util.ArrayList;
import java.util.HashMap;
import java.util.List;
import java.util.logging.Logger;

public class BuroPMImpl {
	Statement st = null;
	int iFolioid = 0;
	int numRegistros = 0;
	long idSegmentoHD = 0L;
	HashMap<String, String> segmentos = null;
	private long idError;
	private String descError;
	private String host;
	private String port;
	private String user;
	private String pass;
	private String code;
	
	
	public int consulta(Connection conn, String Solicitante, int noEtapa, String valorEtapa, int ProductoRequerido, 
			String TipoCuenta, String ClaveUnidadMonetaria, double ImporteContrato, int usuario_id_sol, int usuario_id_aut, 
            String computadora, int confirma, String sucursal) {
		int folio = -1;
		String xConsulta = "";
		DataOutputStream out = null;
	    DataInputStream in = null;
		Logger.getAnonymousLogger().info(Solicitante);
		//String host = "128.9.55.29"; 
		//int port = 9004;
		/* "9992", "9992JUAREZH", "fg57Y8jW" */
		Socket buroSocket;
		String xrespuestaBuro  = "";
		StringBuffer sb;
		String aux = "";
		
		try {
			try{
				// INICIALIZAMOS
				this.idError = 0L;
				this.descError = "";
				 st = conn.createStatement();
				 
				 if (! this.getCredenciales(sucursal)) {
					 throw new Exception(this.idError + ": " + this.descError);
				 }
				 
				 xConsulta = genera_consultaPM(Solicitante);
				//Quitamos la letra Ñ de la cadena de consulta para que no marque error en buro.
				 xConsulta = xConsulta.replace("Ñ", "N");
				 if ("".equals(xConsulta)) {
					 throw new Exception(this.idError + ": " + this.descError);
				 }
				 
				 Logger.getAnonymousLogger().info("------------------------> Consulta: \n" + xConsulta);
				 
			     buroSocket = new Socket(this.host, Integer.parseInt(this.port));
	             buroSocket.setTcpNoDelay(true);
	             buroSocket.setOOBInline(true);
	             
			     out = new DataOutputStream(buroSocket.getOutputStream());
				 out.writeBytes(xConsulta);
				 out.write('\u0013');
				 out.write('\n');
				 out.write('\n');
				 
		         in = new DataInputStream(buroSocket.getInputStream());
		         sb = new StringBuffer();
		         
	             try {
	            	 Logger.getAnonymousLogger().info("empieza lectura");
				     int car = 0;
				     while ((car = in.read()) >= 0) {
				    	 if (car == '\u0013') break;
				    	 sb.append((char) car);
				     }
	             } catch( Exception e ) {
	            	 Logger.getAnonymousLogger().info(e.toString());
	            	 /*for (int i=0; i != e.getStackTrace().length; i++)
	            		 Logger.getAnonymousLogger().info(e.getStackTrace()[i].toString());*/
	             }

				xrespuestaBuro = sb.toString();
	            Logger.getAnonymousLogger().info("termina lectura");
				Logger.getAnonymousLogger().info("------------------------> Regreso: \n" + xrespuestaBuro);
				out.close();
				in.close();
				
	            Logger.getAnonymousLogger().info("Guardamos consulta y respuesta");
				folio = inserta_tabla_consultacirculo(xConsulta, xrespuestaBuro, Solicitante, noEtapa , valorEtapa, ProductoRequerido, 
						TipoCuenta, ClaveUnidadMonetaria, ImporteContrato, usuario_id_sol, usuario_id_aut, computadora, confirma, sucursal, "ok");
			} catch(Exception se) {
				Logger.getAnonymousLogger().info(se.toString());
				folio = -1;
	            folio = inserta_tabla_consultacirculo(xConsulta, xrespuestaBuro, Solicitante, noEtapa , valorEtapa, ProductoRequerido, 
	            		TipoCuenta, ClaveUnidadMonetaria, ImporteContrato, usuario_id_sol, usuario_id_aut, computadora, confirma, sucursal, se.toString());
			}
		} catch( Exception se) {
			Logger.getAnonymousLogger().info(se.toString());
			folio = -1;
	        folio = inserta_tabla_consultacirculo(xConsulta, xrespuestaBuro, Solicitante, noEtapa , valorEtapa, ProductoRequerido, 
	        		TipoCuenta, ClaveUnidadMonetaria, ImporteContrato, usuario_id_sol, usuario_id_aut, computadora, confirma, sucursal, se.toString());
		} finally {
			if (conn != null) {
				try { 
					conn.close(); 
				} catch( Exception e ) {
					e.printStackTrace();
				}
			}
		}
		
		return folio;
	}
	
	public int respuesta(Connection conn, String sfolio) {
		List<String> segmentos = null;
		String sTexto = "";
		String query = "";
		int result = 0;
		boolean errorIntr = false;
		
		try{ 
			// INICIALIZAMOS
			this.idError = 0L;
			this.descError = "";
			st = conn.createStatement();
			
			if (! this.cleanFolio(sfolio)) {
				throw new Exception(this.idError + ": " + this.descError);
			}
		    
		    query = "SELECT respuestaxml FROM consultas_circulo WHERE folioconsulta = '" + sfolio + "'";
			ResultSet res2 = st.executeQuery(query);
			res2.next();
			sTexto = res2.getString(1).trim();
			segmentos = this.getSegmentos(sTexto);
			
			if ("".equals(sTexto.trim()) || segmentos.isEmpty()) {
				this.idError = -5L;
				this.descError = "No existe respuesta de buro registrada";
				throw new Exception(this.idError + ": " + this.descError);
			}
			
			String etiqueta = "";
			for (String segmento : segmentos) {
				etiqueta = segmento.substring(0, 2);
				
				if ("HD".equals(etiqueta)) {
				    Logger.getAnonymousLogger().info("Procesando: " + segmento);
                    procesa_HD(segmento, sfolio);
                } else if ("EM".equals(etiqueta)) {
                    Logger.getAnonymousLogger().info("Procesando: " + segmento);
                    procesa_EM(segmento, sfolio); 
                    procesa_EM_OLD(sfolio, segmento);
                } else if ("HC".equals(etiqueta)) {
                    Logger.getAnonymousLogger().info("Procesando: " + segmento);
                    procesa_HC(segmento, sfolio);
                } else if ("HR".equals(etiqueta)) {
                    Logger.getAnonymousLogger().info("Procesando: " + segmento);
                    procesa_HR(segmento, sfolio);
                } else if ("DC".equals(etiqueta)) {
                    Logger.getAnonymousLogger().info("Procesando: " + segmento);
                    procesa_DC(segmento, sfolio);
                } else if ("AC".equals(etiqueta)) {
                    Logger.getAnonymousLogger().info("Procesando: " + segmento);
                    procesa_AC(segmento, sfolio);
                } else if ("CR".equals(etiqueta)) {
                    Logger.getAnonymousLogger().info("Procesando: " + segmento);
                    procesa_CR(segmento, sfolio);
                    procesa_CR_OLD(sfolio, segmento);
                } else if ("HI".equals(etiqueta)) {
                    Logger.getAnonymousLogger().info("Procesando: " + segmento);
                    procesa_HI(segmento, sfolio);
                } else if ("CO".equals(etiqueta)) {
                    Logger.getAnonymousLogger().info("Procesando: " + segmento);
                    procesa_CO(segmento, sfolio);
                } else if ("FD".equals(etiqueta)) {
                    Logger.getAnonymousLogger().info("Procesando: " + segmento);
                    procesa_FD(segmento, sfolio);
                } else if ("CN".equals(etiqueta)) {
                    Logger.getAnonymousLogger().info("Procesando: " + segmento);
                    procesa_CN(segmento, sfolio);
                } else if ("ER".equals(etiqueta)) {
                    Logger.getAnonymousLogger().info("Procesando: " + segmento);
                    procesa_ER(segmento, sfolio); //Metodo para interpretar el error this.descError
                    errorIntr = true;
                } else if ("CI".equals(etiqueta)) {
                    Logger.getAnonymousLogger().info("Procesando: " + segmento);
                    procesa_CI(segmento, sfolio);
                    procesa_CI_OLD(sfolio, segmento);
                }
				
				if (this.idError != 0L && errorIntr == false) throw new Exception (this.idError + ": " + this.descError);
			}//En base a la respuesta se procesa si hay un detalle en lo que devolvió buro
			if (errorIntr == true) {
				this.idError = -100L;
				String[] errAux = this.descError.split("");
				this.descError = "Error en la etiqueta " + errAux[3] + errAux[4] + " del segmento " + errAux[1] + errAux[2] + " de la consulta.";
			}
			actualizaError_consultacirculo(sfolio, true);
			result = Integer.valueOf(sfolio);
		} catch(Exception e) {
			Logger.getAnonymousLogger().info(e.toString());
			actualizaError_consultacirculo(sfolio, true);
			//System.out.println("Error en la interpretacion de la respuesta de consulta PM");
            /*for (int i = 0; i != e.getStackTrace().length; i++) {
            	Logger.getAnonymousLogger().info(e.getStackTrace()[i].toString());
            }*/
			result = -1;
		} finally {
			if (conn != null) {
				try { 
					conn.close(); 
				} catch( Exception e ) {
					e.printStackTrace();
				}
			}
		}
		
		return result;
	}

	// ----------------------------------------------------------------------------------
	// CONSULTA 
	// ----------------------------------------------------------------------------------
	
	private String genera_consultaPM(String nSolicitante) {
		String header = "";
		String datos  = "";
		String cierre = "";
		int identificadorConsulta = 0;
		String solicitantes = "";
		
		// generamos el IN para consulta con los clientes que se requieran
		this.numRegistros = 0;
		if (nSolicitante.trim().contains(",")) {
			String[] splitted = nSolicitante.trim().split(",");
			for (int i = 0; i < splitted.length; i++) {
				if (! "".equals(splitted[i].trim())) {
					if (! "".equals(solicitantes)) solicitantes += ",";
					solicitantes += "'" + splitted[i].trim() + "'";
					this.numRegistros += 1;
				}
			}
		} else {
			solicitantes = "'" + nSolicitante + "'";
			this.numRegistros = 1;
		}
		
		// Generamos el ID de la consulta
		identificadorConsulta = this.generaIdentificadorConsulta();
		System.out.println("identificadorConsulta: " + identificadorConsulta);
		System.out.println("             clientes: " + this.numRegistros);
		
		// Usuario y contraseña anterior: "MC27341032", "tHFaJIks" -- PM: "9992", "9992JUAREZH", "fg57Y8jW"
		header = genera_HD(identificadorConsulta, this.code, this.user, this.pass);
		datos  = genera_EM(solicitantes);
		cierre = genera_CI(identificadorConsulta);
		
		if ("".equals(header)) {
			this.idError = -3;
			this.descError = "Error al generar consulta - No se pudo generar el segmento encabezado HD";
			return "";
		}
		
		if ("".equals(datos)) {
			this.idError = -3;
			this.descError = "Error al generar consulta - No se pudo generar el segmento de datos EM";
			return "";
		}
		
		return header + datos + cierre;
	}
	
	private String genera_HD(int identificadorConsulta, String codigoInstitucion, String clave, String pass) {
		String encabezado = "";
			
		try {
			encabezado += this.campo("HD", String.valueOf(identificadorConsulta), "N", 4, true, true, "0"); // IDENTIFICADOR DEL ARCHIVO DE CONSULTA
			encabezado += this.campo("00", codigoInstitucion, "N", 4, true, true, "0"); // CODIGO DE INSTITUCION
			encabezado += this.campo("01", clave, "AN", 20, false, true, null);	// CLAVE DEL USUARIO
			encabezado += this.campo("02", pass, "AN", 20, false, true, null); 	// PASSWORD DE ACCESO
			encabezado += this.campo("03", "ES", "A", 2, true, true, null); 	//CODIGO DE LENGUAJE
			encabezado += this.campo("04", "MEX", "A", 3, true, true, null); 	//CODIGO DE PAIS
			encabezado += this.campo("05", "ES", "A", 2, true, true, null); 	//IDIOMA
			encabezado += this.campo("06", String.valueOf(this.numRegistros), "N", 4, false, false, "0"); //NUMERO DE REGISTROS PARA CONSULTA
			encabezado += this.campo("07", "5", "AN", 1, true, true, "0"); //VERSION DEL ARCHIVO DE RESPUESTA
			encabezado +="\n"; // Salto de linea obligatorio
		} catch(Exception e) { 
			Logger.getAnonymousLogger().info(e.toString());
	        for (int i = 0; i != e.getStackTrace().length; i++) {
	        	Logger.getAnonymousLogger().info(e.getStackTrace()[i].toString());
            }

			this.idError = -1;
			this.descError = "Error al generar el segmento HD para la consulta PM.";
			System.out.println(this.idError + " - " + this.descError);
			e.printStackTrace();
			return "";
		}
		
		System.out.println("Segmento HD: " + encabezado);
		return encabezado;
	}
	
	private String genera_EM(String solicitantes) {
		int identificador = 0;
		String value = "";
		String datos = "";
		
		try {
			ResultSet res = getClientesDatos(solicitantes); 
			while (res.next()) {
				identificador += 1;
				value = String.valueOf(identificador);
				datos += this.campo("EM", value, "AN", 20, false, true, ""); // IDENTIFICADOR DE LA CONSULTA

				value = "001"; // 501: Informe Buro | 002: Monitor | 004: Reporte Califica | 005: Reporte Consolidado | 006: Monitor Consolidado | 007: Informe Buro con Historia de Consultas
				datos += this.campo("00", value, "AN", 3, true, true, "0"); // CLAVE DEL PRODUCTO REQUERIDO
				
				value = res.getString("rfc");
				datos += this.campo("01", value, "AN", value.length(), false, true, null); // RFC DEL CLIENTE
				
				value = res.getString("nombre1");
				datos += this.campo("02", value, "AN", 75, false, true, null); // NOMBRE O RAZON SOCIAL DEL CLIENTE
				
				value = res.getString("nombre2");
				datos += this.campo("03", value, "AN", 75, false, false, null); // SEGUNDO NOMBRE
				
				value = res.getString("tipo_persona");
				if (!"M".equals(value)) {
					value = res.getString("ap_paterno");
					datos += this.campo("04", value, "AN", 25, false, true, null); // APELLIDO PATERNO
					
					value = res.getString("ap_materno");
					datos += this.campo("05", value, "AN", 25, false, true, null); // APELLIDO MATERNO
				}
				
				value = res.getString("domicilio1");
				datos += this.campo("06", value, "AN", 40, false, true, null); // PRIMERA LINEA DE DIRECCION
				
				value = res.getString("domicilio2");
				datos += this.campo("07", value, "AN", 40, false, false, null); // SEGUNDA LINEA DE DIRECCION
				
				value = res.getString("codigo_postal");
				datos += this.campo("08", value, "AN", 10, false, true, null); // CODIGO POSTAL
				
				value = res.getString("colonia");
				datos += this.campo("09", value, "AN", 60, false, false, null); // COLONIA O POBLACION
				
				value = res.getString("ciudad");
				datos += this.campo("10", value, "AN", 40, false, true, null); // CIUDAD
				
				value = res.getString("estado");
				datos += this.campo("11", value, "AN", 40, false, true, null); // NOMBRE DEL ESTADO
				
				value = res.getString("pais");
				datos += this.campo("12", value, "AN", 2, true, true, null); // PAIS DE ORIGEN DEL DOMICILIO
				
				value = "S"; // S: Se cuenta con la firma autógrafa de autorización del Cliente. | N: No se cuenta con firma autógrafa
				datos += this.campo("13", value, "AN", 1, true, false, null); // FIRMA DE AUTORIZACION DEL CLIENTE
				
				value = "R"; // R: Obtener información de compañía con mayor porcentaje de similitud | N: No se requiere Informe Buró del expediente del Cliente con ambigüedad
				datos += this.campo("14", value, "AN", 1, true, false, null); // AMBIGUEDAD
				
				value = "S"; // S: Incluir variables CNBV | N: No se requiere incluir variables CNBV
				datos += this.campo("15", value, "A", 1, true, true, null); // INDICADOR DE VARIABLES
				
				value = res.getString("id");
				datos += this.campo("17", value, "AN", 25, false, false, null); // REFERENCIA CREDITICIA
				
				// 1: PM | 2: PFAE | 3: Fondo o Fideicomiso | 4: Gobierno
				value = (("F".equals(res.getString("tipo_persona"))) ? "2" : "1");
				datos += this.campo("19", value, "N", 1, true, false, null); // TIPO DE CLIENTE
				
				/*value = "1"; // El Catálogo Clave de Consolidación, se encuentra en BC Net
				datos += this.campo("20", value, "N", 1, true, false, null); // CLAVE DE CONSOLIDACIÓN*/
				
				datos += "\n"; // Salto de linea obligatorio
			}
			
			if ("".equals(datos.trim())) {
				return "ERROR";
			}
		} catch (Exception e) {
			e.printStackTrace();
			this.idError = -1;
			this.descError = "Error al generar el(los) segmento(s) EM para la consulta PM.";
			System.out.println(this.idError + " - " + this.descError);
			return "";
		}

		System.out.println("Segmento(s) EM: " + datos);
		return datos;
	}

	private String genera_CI(int identificadorConsulta) {
		String segmentoCI = "";
		
		segmentoCI = this.campo("CI", String.valueOf(identificadorConsulta), "N", 4, true, true, "0") + "\n";
		System.out.println("Segmento CI: " + segmentoCI);
		
		return segmentoCI;
	}

	// ----------------------------------------------------------------------------------
	// RESPUESTA 
	// ----------------------------------------------------------------------------------
	
	private void procesa_HD(String value, String sfolio) {
		String query = "INSERT INTO buro_consultapm_hd (id_segmento, id_consulta, clave_retorno, id_transaccion, fecha_consulta, folio_consulta) VALUES (";
		
		try {
			this.segmentos = getSubsegmentos(value);
			if (this.segmentos.isEmpty()) {
				this.idError = -1;
				this.descError = "Error al interpretar el segmento HD de la respuesta de buro.";
				throw new Exception("El segmento no tiene etiquetas.");
			}
			
			// id segmento
			if (this.segmentos.containsKey("HD") && ! "".equals(this.segmentos.get("HD"))) {
				this.idSegmentoHD = Long.valueOf(this.segmentos.get("HD"));
				query += this.segmentos.get("HD");
			} else {
				this.idSegmentoHD = -1L;
				query += "-1";
			}
			
			// id consulta
			if (this.segmentos.containsKey("00") && ! "".equals(this.segmentos.get("00"))) {
				query += ", " + this.segmentos.get("00");
			} else {
				query += ", 0";
			}
			
			// clave retorno
			if (this.segmentos.containsKey("01") && ! "".equals(this.segmentos.get("01"))) {
				query += ", " + this.segmentos.get("01");
			} else {
				query += ", 0";
			}
			
			// transaccion
			if (this.segmentos.containsKey("02") && ! "".equals(this.segmentos.get("02"))) {
				query += ", " + this.segmentos.get("02");
			} else {
				query += ", 0";
			}
			
			// fecha
			if (this.segmentos.containsKey("03") && ! "".equals(this.segmentos.get("03"))) {
				query += ", '" + convierteAFecha(this.segmentos.get("03")) + "'";
			} else {
				query += "NOW()";
			}
			
			query += ", " + sfolio + ");";
		} catch (Exception e) {
			e.printStackTrace();
			this.idError = -6L;
			this.descError = "Error al interpretar el segmento HD de la respuesta de buro - " + e.getMessage() + " [" + value + "]";
			return;
		}
		
		try{
			Logger.getAnonymousLogger().info(query);
			st.execute(query);
		} catch(Exception e) {
			e.printStackTrace();
			this.idError = -7L;
			this.descError = "Error al guardar segmento HD de la respuesta de buro - " + e.getMessage();
            return;
		}
	}

	private void procesa_EM(String value, String sfolio) {
		String query = "INSERT INTO buro_consultapm_em (id_segmento, tipo_persona, rfc, curp, nombre, segundo_nombre, "
				+ "apellido_paterno, apellido_materno, direccion1, direccion2, colonia, municipio, ciudad, estado, codigo_postal, "
				+ "pais, telefono, extension_tel, fax, nacionalidad, calificacion_cartera, codigo_scian1, codigo_scian2, codigo_scian3, "
				+ "uso_futuro, clave_prevencion, num_consultas_entidades_financieras_3_meses, num_consultas_entidades_financieras_12_meses, "
				+ "num_consultas_entidades_financieras_24_meses, num_consultas_entidades_financieras_24mas_meses, "
				+ "num_consultas_empresas_comerciales_3_meses, num_consultas_empresas_comerciales_12_meses, "
				+ "num_consultas_empresas_comerciales_24_meses, num_consultas_empresas_comerciales_24mas_meses, "
				+ "indicador_informacion_adicional, clave_prevencion_persona_relacionada, clave_prevencion_impugnada, "
				+ "clave_prevencion_impugnada_persona_relacionada, id_segmento_hd, folio_consulta) VALUES (";

		try {
			this.segmentos = getSubsegmentos(value);
			if (this.segmentos.isEmpty()) {
				this.idError = -1;
				this.descError = "Error al interpretar el segmento EM de la respuesta de buro.";
				throw new Exception("El segmento no tiene etiquetas.");
			}
			
			// id segmento
			if (this.segmentos.containsKey("EM") && ! "".equals(this.segmentos.get("EM"))) {
				query += this.segmentos.get("EM");
			} else {
				query += "-1";
			}
			
			// id consulta
			if (this.segmentos.containsKey("00") && ! "".equals(this.segmentos.get("00"))) {
				query += ", " + this.segmentos.get("00");
			} else {
				query += ", 0";
			}
			
			if (this.segmentos.containsKey("01") && ! "".equals(this.segmentos.get("01"))) {
				query += ", '" + this.segmentos.get("01") + "'";
			} else {
				query += ", ''";
			}
			
			if (this.segmentos.containsKey("02") && ! "".equals(this.segmentos.get("02"))) {
				query += ", '" + this.segmentos.get("02") + "'";
			} else {
				query += ", ''";
			}
			
			if (this.segmentos.containsKey("03") && ! "".equals(this.segmentos.get("03"))) {
				query += ", '" + this.segmentos.get("03") + "'";
			} else {
				query += ", ''";
			}
			
			if (this.segmentos.containsKey("04") && ! "".equals(this.segmentos.get("04"))) {
				query += ", '" + this.segmentos.get("04") + "'";
			} else {
				query += ", ''";
			}
			
			if (this.segmentos.containsKey("05") && ! "".equals(this.segmentos.get("05"))) {
				query += ", '" + this.segmentos.get("05") + "'";
			} else {
				query += ", ''";
			}
			
			if (this.segmentos.containsKey("06") && ! "".equals(this.segmentos.get("06"))) {
				query += ", '" + this.segmentos.get("06") + "'";
			} else {
				query += ", ''";
			}
			
			if (this.segmentos.containsKey("07") && ! "".equals(this.segmentos.get("07"))) {
				query += ", '" + this.segmentos.get("07") + "'";
			} else {
				query += ", ''";
			}
			
			if (this.segmentos.containsKey("08") && ! "".equals(this.segmentos.get("08"))) {
				query += ", '" + this.segmentos.get("08") + "'";
			} else {
				query += ", ''";
			}
			
			if (this.segmentos.containsKey("09") && ! "".equals(this.segmentos.get("09"))) {
				query += ", '" + this.segmentos.get("09") + "'";
			} else {
				query += ", ''";
			}
			
			if (this.segmentos.containsKey("10") && ! "".equals(this.segmentos.get("10"))) {
				query += ", '" + this.segmentos.get("10") + "'";
			} else {
				query += ", ''";
			}
			
			if (this.segmentos.containsKey("11") && ! "".equals(this.segmentos.get("11"))) {
				query += ", '" + this.segmentos.get("11") + "'";
			} else {
				query += ", ''";
			}
			
			if (this.segmentos.containsKey("12") && ! "".equals(this.segmentos.get("12"))) {
				query += ", '" + this.segmentos.get("12") + "'";
			} else {
				query += ", ''";
			}
			
			if (this.segmentos.containsKey("13") && ! "".equals(this.segmentos.get("13"))) {
				query += ", '" + this.segmentos.get("13") + "'";
			} else {
				query += ", ''";
			}
			
			if (this.segmentos.containsKey("14") && ! "".equals(this.segmentos.get("14"))) {
				query += ", '" + this.segmentos.get("14") + "'";
			} else {
				query += ", ''";
			}
			
			if (this.segmentos.containsKey("15") && ! "".equals(this.segmentos.get("15"))) {
				query += ", '" + this.segmentos.get("15") + "'";
			} else {
				query += ", ''";
			}
			
			if (this.segmentos.containsKey("16") && ! "".equals(this.segmentos.get("16"))) {
				query += ", '" + this.segmentos.get("16") + "'";
			} else {
				query += ", ''";
			}
			
			if (this.segmentos.containsKey("17") && ! "".equals(this.segmentos.get("17"))) {
				query += ", '" + this.segmentos.get("17") + "'";
			} else {
				query += ", ''";
			}
			
			if (this.segmentos.containsKey("18") && ! "".equals(this.segmentos.get("18"))) {
				query += ", '" + this.segmentos.get("18") + "'";
			} else {
				query += ", ''";
			}
			
			if (this.segmentos.containsKey("19") && ! "".equals(this.segmentos.get("19"))) {
				query += ", '" + this.segmentos.get("19") + "'";
			} else {
				query += ", ''";
			}
			
			if (this.segmentos.containsKey("20") && ! "".equals(this.segmentos.get("20"))) {
				query += ", '" + this.segmentos.get("20") + "'";
			} else {
				query += ", ''";
			}
			
			if (this.segmentos.containsKey("21") && ! "".equals(this.segmentos.get("21"))) {
				query += ", '" + this.segmentos.get("21") + "'";
			} else {
				query += ", ''";
			}
			
			if (this.segmentos.containsKey("22") && ! "".equals(this.segmentos.get("22"))) {
				query += ", '" + this.segmentos.get("22") + "'";
			} else {
				query += ", ''";
			}
			
			if (this.segmentos.containsKey("23") && ! "".equals(this.segmentos.get("23"))) {
				query += ", " + this.segmentos.get("23");
			} else {
				query += ", 0";
			}
			
			if (this.segmentos.containsKey("24") && ! "".equals(this.segmentos.get("24"))) {
				query += ", '" + this.segmentos.get("24") + "'";
			} else {
				query += ", ''";
			}
			
			if (this.segmentos.containsKey("25") && ! "".equals(this.segmentos.get("25"))) {
				query += ", " + this.segmentos.get("25");
			} else {
				query += ", 0";
			}
			
			if (this.segmentos.containsKey("26") && ! "".equals(this.segmentos.get("26"))) {
				query += ", " + this.segmentos.get("26");
			} else {
				query += ", 0";
			}
			
			if (this.segmentos.containsKey("27") && ! "".equals(this.segmentos.get("27"))) {
				query += ", " + this.segmentos.get("27");
			} else {
				query += ", 0";
			}
			
			if (this.segmentos.containsKey("28") && ! "".equals(this.segmentos.get("28"))) {
				query += ", " + this.segmentos.get("28");
			} else {
				query += ", 0";
			}
			
			if (this.segmentos.containsKey("29") && ! "".equals(this.segmentos.get("29"))) {
				query += ", " + this.segmentos.get("29");
			} else {
				query += ", 0";
			}
			
			if (this.segmentos.containsKey("30") && ! "".equals(this.segmentos.get("30"))) {
				query += ", " + this.segmentos.get("30");
			} else {
				query += ", 0";
			}
			
			if (this.segmentos.containsKey("31") && ! "".equals(this.segmentos.get("31"))) {
				query += ", " + this.segmentos.get("31");
			} else {
				query += ", 0";
			}
			
			if (this.segmentos.containsKey("32") && ! "".equals(this.segmentos.get("32"))) {
				query += ", " + this.segmentos.get("32");
			} else {
				query += ", 0";
			}
			
			if (this.segmentos.containsKey("33") && ! "".equals(this.segmentos.get("33"))) {
				query += ", '" + this.segmentos.get("33") + "'";
			} else {
				query += ", ''";
			}
			
			if (this.segmentos.containsKey("34") && ! "".equals(this.segmentos.get("34"))) {
				query += ", '" + this.segmentos.get("34") + "'";
			} else {
				query += ", ''";
			}
			
			if (this.segmentos.containsKey("35") && ! "".equals(this.segmentos.get("35"))) {
				query += ", '" + this.segmentos.get("35") + "'";
			} else {
				query += ", ''";
			}
			
			if (this.segmentos.containsKey("36") && ! "".equals(this.segmentos.get("36"))) {
				query += ", '" + this.segmentos.get("36") + "'";
			} else {
				query += ", ''";
			}
			
			query += ", " + this.idSegmentoHD + ", " + sfolio +");";
		} catch (Exception e) {
			e.printStackTrace();
			this.idError = -6L;
			this.descError = "Error al interpretar el segmento EM de la respuesta de buro - " + e.getMessage() + " [" + value + "]";
			return;
		}
		
		try{
			Logger.getAnonymousLogger().info(query);
			st.execute(query);
		} catch(Exception e) {
			e.printStackTrace();
			this.idError = -7L;
			this.descError = "Error al guardar segmento EM de la respuesta de buro - " + e.getMessage();
            return;
		}
	}

	private void procesa_EM_OLD(String sfolio, String segmento) {
		this.segmentos = getSubsegmentos(segmento);
		String insert = "";
		
		// RECUPERAMOS PERSONA
		// -----------------------------------------------------------------------------------------------------
		try{
			insert = insertQueryPersona(this.segmentos, sfolio);
			Logger.getAnonymousLogger().info(insert);
			st.execute(insert);
		} catch(Exception e) {
			Logger.getAnonymousLogger().info(e.toString());
            for (int i = 0; i != e.getStackTrace().length; i++) {
            	Logger.getAnonymousLogger().info(e.getStackTrace()[i].toString());
            }
		}
		
		// RECUPERAMOS DOMICILIO
		// -----------------------------------------------------------------------------------------------------
		
		try{
			insert = insertQueryDomicilio(this.segmentos, sfolio);
			Logger.getAnonymousLogger().info(insert);
			st.execute(insert);
		} catch(Exception e) {
			Logger.getAnonymousLogger().info(e.toString());
            for (int i = 0; i != e.getStackTrace().length; i++) {
            	Logger.getAnonymousLogger().info(e.getStackTrace()[i].toString());
            }
		}
	}

	private void procesa_HC(String value, String sfolio) {
		String query = "INSERT INTO buro_consultapm_hc (id_segmento, fecha, codigo, usuario_reporta, descripcion, id_segmento_hd, folio_consulta) VALUES (";

		try {
			this.segmentos = getSubsegmentos(value);
			if (this.segmentos.isEmpty()) {
				this.idError = -1;
				this.descError = "Error al interpretar el segmento HC de la respuesta de buro.";
				throw new Exception("El segmento no tiene etiquetas.");
			}
			
			if (this.segmentos.containsKey("HC") && ! "".equals(this.segmentos.get("HC"))) {
				query += this.segmentos.get("HC");
			} else {
				query += "-1";
			}
			
			if (this.segmentos.containsKey("00") && ! "".equals(this.segmentos.get("00"))) {
				query += ", '" + this.convierteAFecha(this.segmentos.get("00")) + "'";
			} else {
				query += ", NOW()";
			}
			
			if (this.segmentos.containsKey("01") && ! "".equals(this.segmentos.get("01"))) {
				query += ", " + this.segmentos.get("01");
			} else {
				query += ", 0";
			}
			
			if (this.segmentos.containsKey("02") && ! "".equals(this.segmentos.get("02"))) {
				query += ", '" + this.segmentos.get("02") + "'";
			} else {
				query += ", ''";
			}
			
			if (this.segmentos.containsKey("03") && ! "".equals(this.segmentos.get("03"))) {
				query += ", '" + this.segmentos.get("03") + "'";
			} else {
				query += ", ''";
			}
			
			query += ", " + this.idSegmentoHD + ", " + sfolio + ");";
		} catch (Exception e) {
			e.printStackTrace();
			this.idError = -6L;
			this.descError = "Error al interpretar el segmento HC de la respuesta de buro - " + e.getMessage() + " [" + value + "]";
			return;
		}
		
		try{
			Logger.getAnonymousLogger().info(query);
			st.execute(query);
		} catch(Exception e) {
			e.printStackTrace();
			this.idError = -7L;
			this.descError = "Error al guardar segmento HC de la respuesta de buro - " + e.getMessage();
            return;
		}
	}

	private void procesa_HR(String value, String sfolio) {
		String query = "INSERT INTO buro_consultapm_hr (id_segmento, fecha, codigo, tipo_usuario_reporta, descripcion, id_segmento_hd, folio_consulta) VALUES (";

		try {
			this.segmentos = getSubsegmentos(value);
			if (this.segmentos.isEmpty()) {
				this.idError = -1;
				this.descError = "Error al interpretar el segmento HR de la respuesta de buro.";
				throw new Exception("El segmento no tiene etiquetas.");
			}
			
			if (this.segmentos.containsKey("HR") && ! "".equals(this.segmentos.get("HR"))) {
				query += this.segmentos.get("HR");
			} else {
				query += "-1";
			}
			
			if (this.segmentos.containsKey("00") && ! "".equals(this.segmentos.get("00"))) {
				query += ", '" + this.convierteAFecha(this.segmentos.get("00")) + "'";
			} else {
				query += ", NOW()";
			}
			
			if (this.segmentos.containsKey("01") && ! "".equals(this.segmentos.get("01"))) {
				query += ", " + this.segmentos.get("01");
			} else {
				query += ", 0";
			}
			
			if (this.segmentos.containsKey("02") && ! "".equals(this.segmentos.get("02"))) {
				query += ", '" + this.segmentos.get("02") + "'";
			} else {
				query += ", ''";
			}
			
			if (this.segmentos.containsKey("03") && ! "".equals(this.segmentos.get("03"))) {
				query += ", '" + this.segmentos.get("03") + "'";
			} else {
				query += ", ''";
			}
			
			query += ", " + this.idSegmentoHD + ", " + sfolio + ");";
		} catch (Exception e) {
			e.printStackTrace();
			this.idError = -6L;
			this.descError = "Error al interpretar el segmento HR de la respuesta de buro - " + e.getMessage() + " [" + value + "]";
			return;
		}
		
		try{
			Logger.getAnonymousLogger().info(query);
			st.execute(query);
		} catch(Exception e) {
			e.printStackTrace();
			this.idError = -7L;
			this.descError = "Error al guardar segmento HR de la respuesta de buro - " + e.getMessage();
            return;
		}
	}

	private void procesa_DC(String value, String sfolio) {
		String query = "INSERT INTO buro_consultapm_dc (id_segmento, rfc, declarativa1, declarativa2, declarativa3, declarativa4, declarativa5, " 
				+ "declarativa6, declarativa7, declarativa8, declarativa10, declarativa11, declarativa12, declarativa13, declarativa14, " 
				+ "declarativa15, declarativa16, declarativa17, declarativa18, declarativa19, longitud_declarativa21, declarativa21, " 
				+ "fecha_declarativa, tipo_declarativa, clasif_tipo_otorgante, numero_contrato, tipo_credito, id_segmento_hd, folio_consulta) VALUES (";

		try {
			this.segmentos = getSubsegmentos(value);
			if (this.segmentos.isEmpty()) {
				this.idError = -1;
				this.descError = "Error al interpretar el segmento DC de la respuesta de buro.";
				throw new Exception("El segmento no tiene etiquetas.");
			}
			
			if (this.segmentos.containsKey("DC") && ! "".equals(this.segmentos.get("DC"))) {
				query += this.segmentos.get("DC");
			} else {
				query += "-1";
			}
			
			if (this.segmentos.containsKey("00") && ! "".equals(this.segmentos.get("00"))) {
				query += ", 'STR" + this.segmentos.get("00") + "'";
			} else {
				query += ", ''";
			}
			
			if (this.segmentos.containsKey("01") && ! "".equals(this.segmentos.get("01"))) {
				query += ", '" + this.segmentos.get("01") + "'";
			} else {
				query += ", ''";
			}
			
			if (this.segmentos.containsKey("02") && ! "".equals(this.segmentos.get("02"))) {
				query += ", '" + this.segmentos.get("02") + "'";
			} else {
				query += ", ''";
			}
			
			if (this.segmentos.containsKey("03") && ! "".equals(this.segmentos.get("03"))) {
				query += ", '" + this.segmentos.get("03") + "'";
			} else {
				query += ", ''";
			}
			
			if (this.segmentos.containsKey("04") && ! "".equals(this.segmentos.get("04"))) {
				query += ", '" + this.segmentos.get("04") + "'";
			} else {
				query += ", ''";
			}
			
			if (this.segmentos.containsKey("05") && ! "".equals(this.segmentos.get("05"))) {
				query += ", '" + this.segmentos.get("05") + "'";
			} else {
				query += ", ''";
			}
			
			if (this.segmentos.containsKey("06") && ! "".equals(this.segmentos.get("06"))) {
				query += ", '" + this.segmentos.get("06") + "'";
			} else {
				query += ", ''";
			}
			
			if (this.segmentos.containsKey("07") && ! "".equals(this.segmentos.get("07"))) {
				query += ", '" + this.segmentos.get("07") + "'";
			} else {
				query += ", ''";
			}
			
			if (this.segmentos.containsKey("08") && ! "".equals(this.segmentos.get("08"))) {
				query += ", '" + this.segmentos.get("08") + "'";
			} else {
				query += ", ''";
			}
			
			if (this.segmentos.containsKey("09") && ! "".equals(this.segmentos.get("09"))) {
				query += ", '" + this.segmentos.get("09") + "'";
			} else {
				query += ", ''";
			}
			
			if (this.segmentos.containsKey("10") && ! "".equals(this.segmentos.get("10"))) {
				query += ", '" + this.segmentos.get("10") + "'";
			} else {
				query += ", ''";
			}
			
			if (this.segmentos.containsKey("11") && ! "".equals(this.segmentos.get("11"))) {
				query += ", '" + this.segmentos.get("11") + "'";
			} else {
				query += ", ''";
			}
			
			if (this.segmentos.containsKey("12") && ! "".equals(this.segmentos.get("12"))) {
				query += ", '" + this.segmentos.get("12") + "'";
			} else {
				query += ", ''";
			}
			
			if (this.segmentos.containsKey("13") && ! "".equals(this.segmentos.get("13"))) {
				query += ", '" + this.segmentos.get("13") + "'";
			} else {
				query += ", ''";
			}
			
			if (this.segmentos.containsKey("14") && ! "".equals(this.segmentos.get("14"))) {
				query += ", '" + this.segmentos.get("14") + "'";
			} else {
				query += ", ''";
			}
			
			if (this.segmentos.containsKey("15") && ! "".equals(this.segmentos.get("15"))) {
				query += ", '" + this.segmentos.get("15") + "'";
			} else {
				query += ", ''";
			}
			
			if (this.segmentos.containsKey("16") && ! "".equals(this.segmentos.get("16"))) {
				query += ", '" + this.segmentos.get("16") + "'";
			} else {
				query += ", ''";
			}
			
			if (this.segmentos.containsKey("17") && ! "".equals(this.segmentos.get("17"))) {
				query += ", '" + this.segmentos.get("17") + "'";
			} else {
				query += ", ''";
			}
			
			if (this.segmentos.containsKey("18") && ! "".equals(this.segmentos.get("18"))) {
				query += ", '" + this.segmentos.get("18") + "'";
			} else {
				query += ", ''";
			}
			
			if (this.segmentos.containsKey("19") && ! "".equals(this.segmentos.get("19"))) {
				query += ", '" + this.segmentos.get("19") + "'";
			} else {
				query += ", ''";
			}
			
			if (this.segmentos.containsKey("20") && ! "".equals(this.segmentos.get("20"))) {
				query += ", " + this.segmentos.get("20");
			} else {
				query += ", 0";
			}
			
			if (this.segmentos.containsKey("21") && ! "".equals(this.segmentos.get("21"))) {
				query += ", '" + this.segmentos.get("21") + "'";
			} else {
				query += ", ''";
			}
			
			if (this.segmentos.containsKey("22") && ! "".equals(this.segmentos.get("22"))) {
				query += ", '" + this.convierteAFecha(this.segmentos.get("22")) + "'";
			} else {
				query += ", NOW()";
			}
			
			if (this.segmentos.containsKey("23") && ! "".equals(this.segmentos.get("23"))) {
				query += ", " + this.segmentos.get("23");
			} else {
				query += ", 0";
			}
			
			if (this.segmentos.containsKey("24") && ! "".equals(this.segmentos.get("24"))) {
				query += ", " + this.segmentos.get("24");
			} else {
				query += ", 0";
			}
			
			if (this.segmentos.containsKey("25") && ! "".equals(this.segmentos.get("25"))) {
				query += ", '" + this.segmentos.get("25") + "'";
			} else {
				query += ", ''";
			}
			
			if (this.segmentos.containsKey("26") && ! "".equals(this.segmentos.get("26"))) {
				query += ", " + this.segmentos.get("26");
			} else {
				query += ", 0";
			}
			
			query += ", " + this.idSegmentoHD + ", " + sfolio + ");";
		} catch (Exception e) {
			e.printStackTrace();
			this.idError = -6L;
			this.descError = "Error al interpretar el segmento DC de la respuesta de buro - " + e.getMessage() + " [" + value + "]";
			return;
		}
		
		try{
			Logger.getAnonymousLogger().info(query);
			st.execute(query);
		} catch(Exception e) {
			e.printStackTrace();
			this.idError = -7L;
			this.descError = "Error al guardar segmento DC de la respuesta de buro - " + e.getMessage();
            return;
		}
	}

	private void procesa_AC(String value, String sfolio) {
		String query = "INSERT INTO buro_consultapm_ac (id_segmento, tipo_persona_aval, rfc, curp, nombre_aval, segundo_nombre, " 
				+ "apellido_paterno, apellido_materno, direccion1, direccion2, colonia, municipio, ciudad, estado, codigo_postal, " 
				+ "pais, telefono, extension_tel, fax, tipo_persona, porcentaje_accionistas, cantidad_avalada, id_segmento_hd, folio_consulta) VALUES (";

		try {
			this.segmentos = getSubsegmentos(value);
			if (this.segmentos.isEmpty()) {
				this.idError = -1;
				this.descError = "Error al interpretar el segmento AC de la respuesta de buro.";
				throw new Exception("El segmento no tiene etiquetas.");
			}
			
			if (this.segmentos.containsKey("AC") && ! "".equals(this.segmentos.get("AC"))) {
				query += this.segmentos.get("AC");
			} else {
				query += "-1";
			}
			
			if (this.segmentos.containsKey("00") && ! "".equals(this.segmentos.get("00"))) {
				query += ", '" + this.segmentos.get("00") + "'";
			} else {
				query += ", ''";
			}
			
			if (this.segmentos.containsKey("01") && ! "".equals(this.segmentos.get("01"))) {
				query += ", '" + this.segmentos.get("01") + "'";
			} else {
				query += ", ''";
			}
			
			if (this.segmentos.containsKey("02") && ! "".equals(this.segmentos.get("02"))) {
				query += ", '" + this.segmentos.get("02") + "'";
			} else {
				query += ", ''";
			}
			
			if (this.segmentos.containsKey("03") && ! "".equals(this.segmentos.get("03"))) {
				query += ", '" + this.segmentos.get("03") + "'";
			} else {
				query += ", ''";
			}
			
			if (this.segmentos.containsKey("04") && ! "".equals(this.segmentos.get("04"))) {
				query += ", '" + this.segmentos.get("04") + "'";
			} else {
				query += ", ''";
			}
			
			if (this.segmentos.containsKey("05") && ! "".equals(this.segmentos.get("05"))) {
				query += ", '" + this.segmentos.get("05") + "'";
			} else {
				query += ", ''";
			}
			
			if (this.segmentos.containsKey("06") && ! "".equals(this.segmentos.get("06"))) {
				query += ", '" + this.segmentos.get("06") + "'";
			} else {
				query += ", ''";
			}
			
			if (this.segmentos.containsKey("07") && ! "".equals(this.segmentos.get("07"))) {
				query += ", '" + this.segmentos.get("07") + "'";
			} else {
				query += ", ''";
			}
			
			if (this.segmentos.containsKey("08") && ! "".equals(this.segmentos.get("08"))) {
				query += ", '" + this.segmentos.get("08") + "'";
			} else {
				query += ", ''";
			}
			
			if (this.segmentos.containsKey("09") && ! "".equals(this.segmentos.get("09"))) {
				query += ", '" + this.segmentos.get("09") + "'";
			} else {
				query += ", ''";
			}
			
			if (this.segmentos.containsKey("10") && ! "".equals(this.segmentos.get("10"))) {
				query += ", '" + this.segmentos.get("10") + "'";
			} else {
				query += ", ''";
			}
			
			if (this.segmentos.containsKey("11") && ! "".equals(this.segmentos.get("11"))) {
				query += ", '" + this.segmentos.get("11") + "'";
			} else {
				query += ", ''";
			}
			
			if (this.segmentos.containsKey("12") && ! "".equals(this.segmentos.get("12"))) {
				query += ", '" + this.segmentos.get("12") + "'";
			} else {
				query += ", ''";
			}
			
			if (this.segmentos.containsKey("13") && ! "".equals(this.segmentos.get("13"))) {
				query += ", '" + this.segmentos.get("13") + "'";
			} else {
				query += ", ''";
			}
			
			if (this.segmentos.containsKey("14") && ! "".equals(this.segmentos.get("14"))) {
				query += ", '" + this.segmentos.get("14") + "'";
			} else {
				query += ", ''";
			}
			
			if (this.segmentos.containsKey("15") && ! "".equals(this.segmentos.get("15"))) {
				query += ", '" + this.segmentos.get("15") + "'";
			} else {
				query += ", ''";
			}
			
			if (this.segmentos.containsKey("16") && ! "".equals(this.segmentos.get("16"))) {
				query += ", '" + this.segmentos.get("16") + "'";
			} else {
				query += ", ''";
			}
			
			if (this.segmentos.containsKey("17") && ! "".equals(this.segmentos.get("17"))) {
				query += ", '" + this.segmentos.get("17") + "'";
			} else {
				query += ", ''";
			}
			
			if (this.segmentos.containsKey("18") && ! "".equals(this.segmentos.get("18"))) {
				query += ", " + this.segmentos.get("18");
			} else {
				query += ", 0";
			}
			
			if (this.segmentos.containsKey("19") && ! "".equals(this.segmentos.get("19"))) {
				query += ", " + this.segmentos.get("19");
			} else {
				query += ", 0";
			}
			
			if (this.segmentos.containsKey("20") && ! "".equals(this.segmentos.get("20"))) {
				query += ", " + this.segmentos.get("20");
			} else {
				query += ", 0";
			}
			
			query += ", " + this.idSegmentoHD + ", " + sfolio + ");";
		} catch (Exception e) {
			e.printStackTrace();
			this.idError = -6L;
			this.descError = "Error al interpretar el segmento AC de la respuesta de buro - " + e.getMessage() + " [" + value + "]";
			return;
		}
		
		try{
			Logger.getAnonymousLogger().info(query);
			st.execute(query);
		} catch(Exception e) {
			e.printStackTrace();
			this.idError = -7L;
			this.descError = "Error al guardar segmento AC de la respuesta de buro - " + e.getMessage();
            return;
		}
	}

	private void procesa_CR(String value, String sfolio) {
		String query = "INSERT INTO buro_consultapm_cr (id_segmento, rfc, numero_contrato, tipo_usuario, saldo_inicial, moneda, " 
				+ "fecha_apertura_credito, plazo, tipo_cambio, clave_observacion, tipo_credito, saldo_vigente, saldo_vencido_1_29, " 
				+ "saldo_vencido_30_59, saldo_vencido_60_89, saldo_vencido_90_119, saldo_vencido_120_179, saldo_vencido_180mas, " 
				+ "ultimo_periodo_actualizado, fecha_cierre, pago_cierre, quita, dacion_pago, quebranto, historico_pagos, atraso_mayor, " 
				+ "registro_impugnado, historia_dias, numero_pagos, frecuencia_pagos, monto_pagar, fecha_ultimo_pago, fecha_reestructura, " 
				+ "fecha_primer_incumplimiento, saldo_insoluto_principal, credito_maximo_utilizado, fecha_ingreso_cartera_vencida, id_segmento_hd, folio_consulta) VALUES (";
		
		try {
			this.segmentos = getSubsegmentos(value);
			if (this.segmentos.isEmpty()) {
				this.idError = -1;
				this.descError = "Error al interpretar el segmento CR de la respuesta de buro.";
				throw new Exception("El segmento no tiene etiquetas.");
			}
			
			if (this.segmentos.containsKey("CR") && ! "".equals(this.segmentos.get("CR"))) {
				query += this.segmentos.get("CR");
			} else {
				query += "-1";
			}
			
			if (this.segmentos.containsKey("00") && ! "".equals(this.segmentos.get("00"))) {
				query += ", '" + this.segmentos.get("00") + "'";
			} else {
				query += ", ''";
			}
					
			if (this.segmentos.containsKey("01") && ! "".equals(this.segmentos.get("01"))) {
				query += ", '" + this.segmentos.get("01") + "'";
			} else {
				query += ", ''";
			}
			
			if (this.segmentos.containsKey("02") && ! "".equals(this.segmentos.get("02"))) {
				query += ", '" + this.segmentos.get("02") + "'";
			} else {
				query += ", ''";
			}
			
			if (this.segmentos.containsKey("03") && ! "".equals(this.segmentos.get("03"))) {
				query += ", " + this.segmentos.get("03");
			} else {
				query += ", 0";
			}
			
			if (this.segmentos.containsKey("04") && ! "".equals(this.segmentos.get("04"))) {
				query += ", '" + this.segmentos.get("04") + "'";
			} else {
				query += ", ''";
			}
			
			if (this.segmentos.containsKey("05") && ! "".equals(this.segmentos.get("05"))) {
				query += ", '" + this.convierteAFecha(this.segmentos.get("05")) + "'";
			} else {
				query += ", NOW()";
			}
			
			if (this.segmentos.containsKey("06") && ! "".equals(this.segmentos.get("06"))) {
				query += ", " + this.segmentos.get("06");
			} else {
				query += ", 0";
			}
			
			if (this.segmentos.containsKey("07") && ! "".equals(this.segmentos.get("07"))) {
				query += ", " + this.segmentos.get("07");
			} else {
				query += ", 0";
			}
			
			if (this.segmentos.containsKey("08") && ! "".equals(this.segmentos.get("08"))) {
				query += ", '" + this.segmentos.get("08") + "'";
			} else {
				query += ", ''";
			}
			
			if (this.segmentos.containsKey("09") && ! "".equals(this.segmentos.get("09"))) {
				query += ", " + this.segmentos.get("09");
			} else {
				query += ", 0";
			}
			
			if (this.segmentos.containsKey("10") && ! "".equals(this.segmentos.get("10"))) {
				query += ", " + this.segmentos.get("10");
			} else {
				query += ", 0";
			}
			
			if (this.segmentos.containsKey("11") && ! "".equals(this.segmentos.get("11"))) {
				query += ", " + this.segmentos.get("11");
			} else {
				query += ", 0";
			}
			
			if (this.segmentos.containsKey("12") && ! "".equals(this.segmentos.get("12"))) {
				query += ", " + this.segmentos.get("12");
			} else {
				query += ", 0";
			}
			
			if (this.segmentos.containsKey("13") && ! "".equals(this.segmentos.get("13"))) {
				query += ", " + this.segmentos.get("13");
			} else {
				query += ", 0";
			}
			
			if (this.segmentos.containsKey("14") && ! "".equals(this.segmentos.get("14"))) {
				query += ", " + this.segmentos.get("14");
			} else {
				query += ", 0";
			}
			
			if (this.segmentos.containsKey("15") && ! "".equals(this.segmentos.get("15"))) {
				query += ", " + this.segmentos.get("15");
			} else {
				query += ", 0";
			}
			
			if (this.segmentos.containsKey("16") && ! "".equals(this.segmentos.get("16"))) {
				query += ", " + this.segmentos.get("16");
			} else {
				query += ", 0";
			}
			
			if (this.segmentos.containsKey("17") && ! "".equals(this.segmentos.get("17"))) {
				query += ", '" + this.segmentos.get("17") + "'";
			} else {
				query += ", ''";
			}
			
			if (this.segmentos.containsKey("18") && ! "".equals(this.segmentos.get("18"))) {
				query += ", '" + this.convierteAFecha(this.segmentos.get("18")) + "'";
			} else {
				query += ", now()";
			}
			
			if (this.segmentos.containsKey("19") && ! "".equals(this.segmentos.get("19"))) {
				query += ", " + this.segmentos.get("19");
			} else {
				query += ", 0";
			}
			
			if (this.segmentos.containsKey("20") && ! "".equals(this.segmentos.get("20"))) {
				query += ", " + this.segmentos.get("20");
			} else {
				query += ", 0";
			}
			
			if (this.segmentos.containsKey("21") && ! "".equals(this.segmentos.get("21"))) {
				query += ", " + this.segmentos.get("21");
			} else {
				query += ", 0";
			}
			
			if (this.segmentos.containsKey("22") && ! "".equals(this.segmentos.get("22"))) {
				query += ", " + this.segmentos.get("22");
			} else {
				query += ", 0";
			}
			
			if (this.segmentos.containsKey("23") && ! "".equals(this.segmentos.get("23"))) {
				query += ", '" + this.segmentos.get("23") + "'";
			} else {
				query += ", ''";
			}
			
			if (this.segmentos.containsKey("24") && ! "".equals(this.segmentos.get("24"))) {
				query += ", " + this.segmentos.get("24");
			} else {
				query += ", 0";
			}
			
			if (this.segmentos.containsKey("25") && ! "".equals(this.segmentos.get("25"))) {
				query += ", '" + this.segmentos.get("25") + "'";
			} else {
				query += ", ''";
			}
			
			if (this.segmentos.containsKey("26") && ! "".equals(this.segmentos.get("26"))) {
				query += ", '" + this.segmentos.get("26") + "'";
			} else {
				query += ", ''";
			}
			
			if (this.segmentos.containsKey("27") && ! "".equals(this.segmentos.get("27"))) {
				query += ", " + this.segmentos.get("27");
			} else {
				query += ", 0";
			}
			
			if (this.segmentos.containsKey("28") && ! "".equals(this.segmentos.get("28"))) {
				query += ", " + this.segmentos.get("28");
			} else {
				query += ", 0";
			}
			
			if (this.segmentos.containsKey("29") && ! "".equals(this.segmentos.get("29"))) {
				query += ", " + this.segmentos.get("29");
			} else {
				query += ", 0";
			}
			
			if (this.segmentos.containsKey("30") && ! "".equals(this.segmentos.get("30"))) {
				query += ", '" + this.convierteAFecha(this.segmentos.get("30")) + "'";
			} else {
				query += ", now()";
			}
			
			if (this.segmentos.containsKey("31") && ! "".equals(this.segmentos.get("31"))) {
				query += ", '" + this.convierteAFecha(this.segmentos.get("31")) + "'";
			} else {
				query += ", now()";
			}
			
			if (this.segmentos.containsKey("32") && ! "".equals(this.segmentos.get("32"))) {
				query += ", '" + this.convierteAFecha(this.segmentos.get("32")) + "'";
			} else {
				query += ", now()";
			}
			
			if (this.segmentos.containsKey("33") && ! "".equals(this.segmentos.get("33"))) {
				query += ", " + this.segmentos.get("33");
			} else {
				query += ", 0";
			}
			
			if (this.segmentos.containsKey("34") && ! "".equals(this.segmentos.get("34"))) {
				query += ", " + this.segmentos.get("34");
			} else {
				query += ", 0";
			}
			
			if (this.segmentos.containsKey("35") && ! "".equals(this.segmentos.get("35"))) {
				query += ", '" + this.convierteAFecha(this.segmentos.get("35")) + "'";
			} else {
				query += ", now()";
			}
			
			query += ", " + this.idSegmentoHD + ", " + sfolio + ");";
		} catch (Exception e) {
			e.printStackTrace();
			this.idError = -6L;
			this.descError = "Error al interpretar el segmento CR de la respuesta de buro - " + e.getMessage() + " [" + value + "]";
			return;
		}
		
		try{
			Logger.getAnonymousLogger().info(query);
			st.execute(query);
		} catch(Exception e) {
			e.printStackTrace();
			this.idError = -7L;
			this.descError = "Error al guardar segmento CR de la respuesta de buro - " + e.getMessage();
            return;
		}
	}

	private void procesa_CR_OLD(String sfolio, String segmento) {
		String query = "";
		
		try {
			this.segmentos = getSubsegmentos(segmento);
			if (this.segmentos.isEmpty()) {
				this.idError = -1;
				this.descError = "Error al interpretar el segmento CR de la respuesta de buro.";
				throw new Exception("El segmento no tiene etiquetas.");
			}
			
			// RECUPERAMOS CUENTAS
			// -----------------------------------------------------------------------------------------------------
			try{
				query = insertQueryCuentas(this.segmentos, sfolio);
				Logger.getAnonymousLogger().info(query);
				st.execute(query);
			} catch(Exception e) {
				Logger.getAnonymousLogger().info(e.toString());
	            for (int i = 0; i != e.getStackTrace().length; i++) {
	            	Logger.getAnonymousLogger().info(e.getStackTrace()[i].toString());
	            }
			}
		} catch (Exception e) {
			System.out.println("Error al interpretar el segmento CR: " + segmento);
			e.printStackTrace();
			this.idError = -1;
			this.descError = "Error al interpretar el segmento CR de la respuesta de buro: " + e.getMessage();
			System.out.println("Error al interpretar el segmento CR: " + segmento);
			return;
		}
	}
	
	private void procesa_HI(String value, String sfolio) {
		String query = "INSERT INTO buro_consultapm_hi (id_segmento, rfc, periodo, saldo_vigente, saldo_vencido_1_29, saldo_vencido_30_59, " 
				+ "saldo_vencido_60_89, saldo_vencido_90mas, calif_cartera, maximo_saldo_vencido, mayor_num_dias_vencido, id_segmento_hd, folio_consulta) VALUES (";

		try {
			this.segmentos = getSubsegmentos(value);
			if (this.segmentos.isEmpty()) {
				this.idError = -1;
				this.descError = "Error al interpretar el segmento HI de la respuesta de buro.";
				throw new Exception("El segmento no tiene etiquetas.");
			}
			
			if (this.segmentos.containsKey("HI") && ! "".equals(this.segmentos.get("HI"))) {
				query += this.segmentos.get("HI");
			} else {
				query += "-1";
			}
			
			if (this.segmentos.containsKey("00") && ! "".equals(this.segmentos.get("00"))) {
				query += ", '" + this.segmentos.get("00") + "'";
			} else {
				query += ", ''";
			}
			
			if (this.segmentos.containsKey("01") && ! "".equals(this.segmentos.get("01"))) {
				query += ", '" + this.segmentos.get("01") + "'";
			} else {
				query += ", ''";
			}
			
			if (this.segmentos.containsKey("02") && ! "".equals(this.segmentos.get("02"))) {
				query += ", " + this.segmentos.get("02");
			} else {
				query += ", 0";
			}
			
			if (this.segmentos.containsKey("03") && ! "".equals(this.segmentos.get("03"))) {
				query += ", " + this.segmentos.get("03");
			} else {
				query += ", 0";
			}
			
			if (this.segmentos.containsKey("04") && ! "".equals(this.segmentos.get("04"))) {
				query += ", " + this.segmentos.get("04");
			} else {
				query += ", 0";
			}
			
			if (this.segmentos.containsKey("05") && ! "".equals(this.segmentos.get("05"))) {
				query += ", " + this.segmentos.get("05");
			} else {
				query += ", 0";
			}
			
			if (this.segmentos.containsKey("06") && ! "".equals(this.segmentos.get("06"))) {
				query += ", " + this.segmentos.get("06");
			} else {
				query += ", 0";
			}
			
			if (this.segmentos.containsKey("07") && ! "".equals(this.segmentos.get("07"))) {
				query += ", '" + this.segmentos.get("07") +"'";
			} else {
				query += ", ''";
			}
			
			if (this.segmentos.containsKey("08") && ! "".equals(this.segmentos.get("08"))) {
				query += ", " + this.segmentos.get("08");
			} else {
				query += ", 0";
			}
			
			if (this.segmentos.containsKey("09") && ! "".equals(this.segmentos.get("09"))) {
				query += ", " + this.segmentos.get("09");
			} else {
				query += ", 0";
			}
			
			query += ", " + this.idSegmentoHD + ", " + sfolio + ");";
		} catch (Exception e) {
			e.printStackTrace();
			this.idError = -6L;
			this.descError = "Error al interpretar el segmento HI de la respuesta de buro - " + e.getMessage() + " [" + value + "]";
			return;
		}
		
		try{
			Logger.getAnonymousLogger().info(query);
			st.execute(query);
		} catch(Exception e) {
			e.printStackTrace();
			this.idError = -7L;
			this.descError = "Error al guardar segmento HI de la respuesta de buro - " + e.getMessage();
            return;
		}
	}

	@SuppressWarnings("unused")
	private void procesa_HI_OLD(String sfolio, String segmento) {
		String query = "";
		
		try {
			this.segmentos = getSubsegmentos(segmento);
			if (this.segmentos.isEmpty()) {
				this.idError = -1;
				this.descError = "Error al interpretar el segmento CR de la respuesta de buro.";
				throw new Exception("El segmento no tiene etiquetas.");
			}
			
			// RECUPERAMOS CONSULTAS EFECTUADAS (HISTORICO)
			// -----------------------------------------------------------------------------------------------------
			try{
				query = insertQueryConsultasEfectuadas(this.segmentos, sfolio);
				Logger.getAnonymousLogger().info(query);
				st.execute(query);
			} catch(Exception e) {
				Logger.getAnonymousLogger().info(e.toString());
	            for (int i = 0; i != e.getStackTrace().length; i++) {
	            	Logger.getAnonymousLogger().info(e.getStackTrace()[i].toString());
	            }
			}
		} catch (Exception e) {
			System.out.println("Error al interpretar el segmento HI: " + segmento);
			e.printStackTrace();
			this.idError = -1;
			this.descError = "Error al interpretar el segmento HI de la respuesta de buro: " + e.getMessage();
			System.out.println("Error al interpretar el segmento HI: " + segmento);
			return;
		}
	}
	
	private void procesa_CO(String value, String sfolio) {
		String query = "INSERT INTO buro_consultapm_co (id_segmento, rfc, num_consecutivo_usuario, saldo_total, saldo_vigente, " 
				+ "saldo_vencido, saldo_vencido_1_29, saldo_vencido_30_59, saldo_vencido_60_89, saldo_vencido_90_119, saldo_vencido_120_179, " 
				+ "saldo_vencido_180mas, ultimo_periodo_actualizado, maximo_saldo_vencido, saldo_promedio, historico_pagos, registro_impugnado, " 
				+ "id_segmento_hd, folio_consulta) VALUES (";

		try {
			this.segmentos = getSubsegmentos(value);
			if (this.segmentos.isEmpty()) {
				this.idError = -1;
				this.descError = "Error al interpretar el segmento CO de la respuesta de buro.";
				throw new Exception("El segmento no tiene etiquetas.");
			}
			
			if (this.segmentos.containsKey("CO") && ! "".equals(this.segmentos.get("CO"))) {
				query += this.segmentos.get("CO");
			} else {
				query += "-1";
			}
			
			if (this.segmentos.containsKey("00") && ! "".equals(this.segmentos.get("00"))) {
				query += ", '" + this.segmentos.get("00") + "'";
			} else {
				query += ", ''";
			}
			
			if (this.segmentos.containsKey("01") && ! "".equals(this.segmentos.get("01"))) {
				query += ", " + this.segmentos.get("01");
			} else {
				query += ", 0";
			}
			
			if (this.segmentos.containsKey("02") && ! "".equals(this.segmentos.get("02"))) {
				query += ", " + this.segmentos.get("02");
			} else {
				query += ", 0";
			}
			
			if (this.segmentos.containsKey("03") && ! "".equals(this.segmentos.get("03"))) {
				query += ", " + this.segmentos.get("03");
			} else {
				query += ", 0";
			}
			
			if (this.segmentos.containsKey("04") && ! "".equals(this.segmentos.get("04"))) {
				query += ", " + this.segmentos.get("04");
			} else {
				query += ", 0";
			}
			
			if (this.segmentos.containsKey("05") && ! "".equals(this.segmentos.get("05"))) {
				query += ", " + this.segmentos.get("05");
			} else {
				query += ", 0";
			}
			
			if (this.segmentos.containsKey("06") && ! "".equals(this.segmentos.get("06"))) {
				query += ", " + this.segmentos.get("06");
			} else {
				query += ", 0";
			}
			
			if (this.segmentos.containsKey("07") && ! "".equals(this.segmentos.get("07"))) {
				query += ", " + this.segmentos.get("07");
			} else {
				query += ", 0";
			}
			
			if (this.segmentos.containsKey("08") && ! "".equals(this.segmentos.get("08"))) {
				query += ", " + this.segmentos.get("08");
			} else {
				query += ", 0";
			}
			
			if (this.segmentos.containsKey("09") && ! "".equals(this.segmentos.get("09"))) {
				query += ", " + this.segmentos.get("09");
			} else {
				query += ", 0";
			}
			
			if (this.segmentos.containsKey("10") && ! "".equals(this.segmentos.get("10"))) {
				query += ", " + this.segmentos.get("10");
			} else {
				query += ", 0";
			}
			
			if (this.segmentos.containsKey("11") && ! "".equals(this.segmentos.get("11"))) {
				query += ", '" + this.segmentos.get("11") + "'";
			} else {
				query += ", ''";
			}
			
			if (this.segmentos.containsKey("12") && ! "".equals(this.segmentos.get("12"))) {
				query += ", " + this.segmentos.get("12");
			} else {
				query += ", 0";
			}
			
			if (this.segmentos.containsKey("13") && ! "".equals(this.segmentos.get("13"))) {
				query += ", " + this.segmentos.get("13");
			} else {
				query += ", 0";
			}
			
			if (this.segmentos.containsKey("14") && ! "".equals(this.segmentos.get("14"))) {
				query += ", '" + this.segmentos.get("14") + "'";
			} else {
				query += ", ''";
			}
			
			if (this.segmentos.containsKey("15") && ! "".equals(this.segmentos.get("15"))) {
				query += ", '" + this.segmentos.get("15") + "'";
			} else {
				query += ", ''";
			}
			
			query += ", " + this.idSegmentoHD + ", " + sfolio + ");";
		} catch (Exception e) {
			e.printStackTrace();
			this.idError = -6L;
			this.descError = "Error al interpretar el segmento CO de la respuesta de buro - " + e.getMessage() + " [" + value + "]";
			return;
		}
		
		try{
			Logger.getAnonymousLogger().info(query);
			st.execute(query);
		} catch(Exception e) {
			e.printStackTrace();
			this.idError = -7L;
			this.descError = "Error al guardar segmento CO de la respuesta de buro - " + e.getMessage();
            return;
		}
	}

	private void procesa_FD(String value, String sfolio) {
		String query = "INSERT INTO buro_consultapm_fd (id_segmento, clave, nombre, valor_caracteristica, codigo_error, id_segmento_hd, folio_consulta) VALUES (";

		try {
			this.segmentos = getSubsegmentos(value);
			if (this.segmentos.isEmpty()) {
				this.idError = -1;
				this.descError = "Error al interpretar el segmento FD de la respuesta de buro.";
				throw new Exception("El segmento no tiene etiquetas.");
			}
			
			if (this.segmentos.containsKey("FD") && ! "".equals(this.segmentos.get("FD"))) {
				query += this.segmentos.get("FD");
			} else {
				query += "-1";
			}
			
			if (this.segmentos.containsKey("00") && ! "".equals(this.segmentos.get("00"))) {
				query += ", '" + this.segmentos.get("00") + "'";
				this.segmentos.put("01", this.getNombreClaveCalifica(this.segmentos.get("00")));
			} else {
				query += ", ''";
			}
			
			if (this.segmentos.containsKey("01") && ! "".equals(this.segmentos.get("01"))) {
				query += ", '" + this.segmentos.get("01") + "'";
			} else {
				query += ", ''";
			}
			
			if (this.segmentos.containsKey("02") && ! "".equals(this.segmentos.get("02"))) {
				query += ", '" + this.segmentos.get("02") + "'";
			} else {
				query += ", ''";
			}
			
			if (this.segmentos.containsKey("03") && ! "".equals(this.segmentos.get("03"))) {
				query += ", '" + this.segmentos.get("03") + "'";
			} else {
				query += ", ''";
			}
			
			query += ", " + this.idSegmentoHD + ", " + sfolio + ");";
		} catch (Exception e) {
			e.printStackTrace();
			this.idError = -6L;
			this.descError = "Error al interpretar el segmento FD de la respuesta de buro - " + e.getMessage() + " [" + value + "]";
			return;
		}
		
		try{
			Logger.getAnonymousLogger().info(query);
			st.execute(query);
		} catch(Exception e) {
			e.printStackTrace();
			this.idError = -7L;
			this.descError = "Error al guardar segmento FD de la respuesta de buro - " + e.getMessage();
            return;
		}
	}

	private void procesa_CN(String value, String sfolio) {
		String query = "INSERT INTO buro_consultapm_cn (id_segmento, rfc, fecha_consulta, tipo_usuario, id_segmento_hd, folio_consulta) VALUES (";

		try {
			this.segmentos = getSubsegmentos(value);
			if (this.segmentos.isEmpty()) {
				this.idError = -1;
				this.descError = "Error al interpretar el segmento CN de la respuesta de buro.";
				throw new Exception("El segmento no tiene etiquetas.");
			}
			
			if (this.segmentos.containsKey("CN") && ! "".equals(this.segmentos.get("CN"))) {
				query += this.segmentos.get("CN");
			} else {
				query += "-1";
			}
			
			if (this.segmentos.containsKey("00") && ! "".equals(this.segmentos.get("00"))) {
				query += ", '" + this.segmentos.get("00") + "'";
			} else {
				query += ", ''";
			}
			
			if (this.segmentos.containsKey("01") && ! "".equals(this.segmentos.get("01"))) {
				query += ", '" + this.convierteAFecha(this.segmentos.get("01")) + "'";
			} else {
				query += ", NOW()";
			}
			
			if (this.segmentos.containsKey("02") && ! "".equals(this.segmentos.get("02"))) {
				query += ", '" + this.segmentos.get("02") + "'";
			} else {
				query += ", ''";
			}
			
			query += ", " + this.idSegmentoHD + ", " + sfolio + ");";
		} catch (Exception e) {
			e.printStackTrace();
			this.idError = -6L;
			this.descError = "Error al interpretar el segmento CN de la respuesta de buro - " + e.getMessage() + " [" + value + "]";
			return;
		}
		
		try{
			Logger.getAnonymousLogger().info(query);
			st.execute(query);
		} catch(Exception e) {
			e.printStackTrace();
			this.idError = -7L;
			this.descError = "Error al guardar segmento CN de la respuesta de buro - " + e.getMessage();
            return;
		}
	}

	private void procesa_ER(String value, String sfolio) {
		String query = "INSERT INTO buro_consultapm_er (id_segmento, producto, segmento_req_no_proporcionado, campo_req_no_propercionado, " 
				+ "rfc_invalido, error_integrar_datos, error_generar_respuesta, problema_conexion, error_ejecucion, error_desconocido, id_segmento_hd, folio_consulta) VALUES (";

		try {
			this.segmentos = getSubsegmentos(value);
			if (this.segmentos.isEmpty()) {
				this.idError = -1;
				this.descError = "Error al interpretar el segmento ER de la respuesta de buro.";
				throw new Exception("El segmento no tiene etiquetas.");
			}
			
			// id_segmento
			if (this.segmentos.containsKey("ER") && ! "".equals(this.segmentos.get("ER"))) {
				query += this.segmentos.get("ER");
			} else {
				query += "-1";
			}
			
			// producto
			if (this.segmentos.containsKey("00") && ! "".equals(this.segmentos.get("00"))) {
				query += ", '" + this.segmentos.get("00") + "'";
				this.descError = this.segmentos.get("00");
			} else {
				query += ", ''";
			}
			
			// segmento_req_no_proporcionado
			if (this.segmentos.containsKey("01") && ! "".equals(this.segmentos.get("01"))) {
				query += ", '" + this.segmentos.get("01") + "'";
				this.descError = this.segmentos.get("01");
			} else {
				query += ", ''";
			}
			
			if (this.segmentos.containsKey("02") && ! "".equals(this.segmentos.get("02"))) {
				query += ", '" + this.segmentos.get("02") + "'";
				this.descError = this.segmentos.get("02");
			} else {
				query += ", ''";
			}
			
			if (this.segmentos.containsKey("03") && ! "".equals(this.segmentos.get("03"))) {
				query += ", '" + this.segmentos.get("03") + "'";
				this.descError = this.segmentos.get("03");
			} else {
				query += ", ''";
			}
			
			if (this.segmentos.containsKey("04") && ! "".equals(this.segmentos.get("04"))) {
				query += ", '" + this.segmentos.get("04") + "'";
				this.descError = this.segmentos.get("04");
			} else {
				query += ", ''";
			}
			
			if (this.segmentos.containsKey("05") && ! "".equals(this.segmentos.get("05"))) {
				query += ", '" + this.segmentos.get("05") + "'";
				this.descError = this.segmentos.get("05");
			} else {
				query += ", ''";
			}
			
			if (this.segmentos.containsKey("06") && ! "".equals(this.segmentos.get("06"))) {
				query += ", '" + this.segmentos.get("06") + "'";
				this.descError = this.segmentos.get("06");
			} else {
				query += ", ''";
			}
			
			if (this.segmentos.containsKey("07") && ! "".equals(this.segmentos.get("07"))) {
				query += ", '" + this.segmentos.get("07") + "'";
				this.descError = this.segmentos.get("07");
			} else {
				query += ", ''";
			}
			
			if (this.segmentos.containsKey("08") && ! "".equals(this.segmentos.get("08"))) {
				query += ", '" + this.segmentos.get("08") + "'";
				this.descError = this.segmentos.get("08");
			} else {
				query += ", ''";
			}
			
			query += ", " + this.idSegmentoHD + ", " + sfolio + ");";
		} catch (Exception e) {
			e.printStackTrace();
			this.idError = -6L;
			this.descError = "Error al interpretar el segmento ER de la respuesta de buro - " + e.getMessage() + " [" + value + "]";
			return;
		}
		
		try{
			Logger.getAnonymousLogger().info(query);
			st.execute(query);
		} catch(Exception e) {
			e.printStackTrace();
			this.idError = -7L;
			this.descError = "Error al guardar segmento ER de la respuesta de buro - " + e.getMessage();
            return;
		}
	}

	private void procesa_CI(String value, String sfolio) {
		String query = "INSERT INTO buro_consultapm_ci (id_segmento, id_consulta, id_transaccion, folio_consulta) VALUES (";
		
		try {
			this.segmentos = getSubsegmentos(value);
			if (this.segmentos.isEmpty()) {
				this.idError = -1;
				this.descError = "Error al interpretar el segmento CI de la respuesta de buro.";
				throw new Exception("El segmento no tiene etiquetas.");
			}
			
			// id_segmento
			if (this.segmentos.containsKey("CI") && ! "".equals(this.segmentos.get("CI"))) {
				query += this.segmentos.get("CI");
			} else {
				query += "-1";
			}
			
			// id_consulta
			if (this.segmentos.containsKey("00") && ! "".equals(this.segmentos.get("00"))) {
				query += ", " + this.segmentos.get("00");
			} else {
				query += ", -1";
			}
			
			// id_transaccion
			if (this.segmentos.containsKey("02") && ! "".equals(this.segmentos.get("02"))) {
				query += ", " + this.segmentos.get("02");
			} else {
				query += ", -1";
			}
			
			query += ", " + sfolio + ");";
		} catch (Exception e) {
			e.printStackTrace();
			this.idError = -6L;
			this.descError = "Error al interpretar el segmento CI de la respuesta de buro - " + e.getMessage() + " [" + value + "]";
			return;
		}
		
		try{
			Logger.getAnonymousLogger().info(query);
			st.execute(query);
		} catch(Exception e) {
			e.printStackTrace();
			this.idError = -7L;
			this.descError = "Error al guardar segmento CI de la respuesta de buro - " + e.getMessage();
            return;
		}
	}
	
	private void procesa_CI_OLD(String sfolio, String segmento) {
		String query = "";
		
		try {
			this.segmentos = getSubsegmentos(segmento);
			if (this.segmentos.isEmpty()) {
				this.idError = -1;
				this.descError = "Error al interpretar el segmento CI de la respuesta de buro.";
				throw new Exception("El segmento no tiene etiquetas.");
			}
			
			// id_transaccion
			if (! this.segmentos.containsKey("02") || "".equals(this.segmentos.get("02"))) {
				this.idError = -1;
				this.descError = "El segmento CI no contiene la etiqueta 02 correspondiente al ID TRANSACCION asignado por Buro de Credito.";
				throw new Exception(this.descError);
			}
			
			// RECUPERAMOS id_transaccion QUE SE INTERPRETA COMO CUENTA BURO Y CONTROL
			// -----------------------------------------------------------------------------------------------------
			try{
				query = "UPDATE consultas_circulo " 
					  + "SET control = '" + this.segmentos.get("02") + "', cuenta_buro = "+ this.segmentos.get("02") + ", fecha_creacion = CURRENT_TIMESTAMP " 
					  + "WHERE folioconsulta = " + sfolio;
				Logger.getAnonymousLogger().info(query);
				st.execute(query);
			} catch(Exception e) {
				Logger.getAnonymousLogger().info(e.toString());
	            for (int i = 0; i != e.getStackTrace().length; i++) {
	            	Logger.getAnonymousLogger().info(e.getStackTrace()[i].toString());
	            }
			}
		} catch (Exception e) {
			System.out.println("Error al interpretar el segmento CI: " + segmento);
			e.printStackTrace();
			this.idError = -1;
			this.descError = "Error al interpretar el segmento CI de la respuesta de buro: " + e.getMessage();
			System.out.println("Error al interpretar el segmento CI: " + segmento);
			return;
		}
	}
	
	// ----------------------------------------------------------------------------------
	// UTILERIAS
	// ----------------------------------------------------------------------------------
	
	private boolean getCredenciales(String idSucursal) throws Exception {
		String sSql = "";
		
		try {
			this.host = "";
			this.port = "";
			this.user = "";
			this.pass = "";
			this.code = "";
			sSql = "SELECT TRIM(COALESCE(usuario_buro_pm, '')) AS user, "
					+ "    TRIM(COALESCE(contrasena_buro_pm, '')) AS pass, "
					+ "    TRIM(COALESCE(servidor_buro_pm, '')) AS host, "
					+ "    TRIM(COALESCE(puerto_buro_pm, '')) AS port, "
					+ "    TRIM(COALESCE(codigo_institucion_buro_pm, '')) AS code "
					+ "FROM agente a INNER JOIN cat_empresas e ON e.clave = a.empresa "
					+ "WHERE a.agente_id = " + idSucursal + ";";
			
			ResultSet rs = st.executeQuery(sSql);
			if (! rs.next())
				return false;

			this.host = rs.getString("host");
			this.port = rs.getString("port");
			this.user = rs.getString("user");
			this.pass = rs.getString("pass");
			this.code = rs.getString("code");

			if ("".equals(this.host)) throw new Exception("No tiene asignado el servidor para consulta de personas morales con buro de credito");
			if ("".equals(this.port)) throw new Exception("No tiene asignado el puerto para consulta de personas morales con buro de credito");
			if ("".equals(this.user)) throw new Exception("No tiene asignado el usuario para consulta de personas morales con buro de credito");
			if ("".equals(this.pass)) throw new Exception("No tiene asignado la contraseña para consulta de personas morales con buro de credito");
			if ("".equals(this.code)) throw new Exception("No tiene asignado el codigo de institucion para consulta de personas morales con buro de credito");
			
			return true;
		} catch (Exception e) {
			this.idError = -2;
			this.descError = "Error al obtener las credenciales para consulta a buro: " + e.getMessage();
			return false;
		}
	}
	
	private ResultSet getClientesDatos(String solicitantes) {
		String sSql = "";
		
		try {
			sSql = "SELECT s.numero AS id"
					+ "    , TRIM(COALESCE(s.rfc1, '') || COALESCE(s.rfc2, '') || COALESCE(s.rfc3, '')) AS rfc"
					+ "    , TRIM(COALESCE(s.nombre, '')) AS nombre1"
					+ "    , '' AS nombre2"
					+ "    , CASE s.t_persona "
					+ "        WHEN 'F' THEN CASE "
					+ "            WHEN COALESCE(TRIM(s.apellidos), '') <> '' THEN COALESCE(TRIM(s.apellidos), '') "
					+ "            WHEN COALESCE(TRIM(s.apellidos), '') = '' AND COALESCE(TRIM(s.apellido_m), '') <> '' THEN COALESCE(TRIM(s.apellido_m), '') "
					+ "            ELSE '' END "
					+ "        ELSE '' "
					+ "        END AS ap_paterno"
					+ "    , CASE s.t_persona "
					+ "        WHEN 'F' THEN CASE "
					+ "            WHEN COALESCE(TRIM(s.apellidos), '') = '' AND COALESCE(TRIM(s.apellido_m), '') <> '' THEN 'NO PROPORCIONADO' "
					+ "            ELSE COALESCE(TRIM(s.apellido_m), '') END "
					+ "        ELSE 'NO PROPORCIONADO' "
					+ "        END AS ap_materno"
					+ "    , TRIM(s.t_persona) AS tipo_persona"
					+ "    , TRIM(COALESCE(s.domicilio, '')) AS domicilio1"
					+ "    , '' AS domicilio2"
					+ "    , COALESCE(col.cp, '00000') AS codigo_postal"
					+ "    , TRIM(COALESCE(col.nombre, '')) AS colonia"
					+ "    , TRIM(COALESCE(loc.descripcion, '')) AS ciudad"
					+ "    , TRIM(COALESCE(edo.clave_buro, '')) AS estado"
					+ "    , CASE WHEN edo.clave IS NOT NULL THEN 'MX' ELSE '' END AS pais "
					+ "FROM solicitante s"
					+ "    LEFT JOIN colonia col on col.clave = s.colonia"
					+ "    LEFT JOIN localidades loc on loc.clave = col.localidad_id"
					+ "    LEFT JOIN catmpio mun on mun.cmpiompio=loc.catmpio_id"
					+ "    LEFT JOIN estatal edo on edo.clave = mun.estado_id "
					+ "WHERE numero IN ("+ solicitantes + ");";
			
			return st.executeQuery(sSql);
		} catch (SQLException e) {
			e.printStackTrace();
			return null;
		}
	}

	private int inserta_tabla_consultacirculo(String consultaEnviada, String res, String idsol, int noEtapa, String valorEtapa, 
			int ProductoRequerido, String TipoCuenta, String ClaveUnidadMonetaria, double ImporteContrato, 
			int usuario_id_sol,int usuario_id_aut, String computadora,int confirma, String sucursal, String error) {
		String sValorn = "";
		String sInsert = "";
		
		try {
			try {
				sValorn = "select nextval('Consultas_circulo_s')";
				ResultSet res2 = st.executeQuery(sValorn);
				res2.next();
				iFolioid = res2.getInt(1);		
			} catch(Exception e) {
				Logger.getAnonymousLogger().info(e.toString());
	            for (int i = 0; i != e.getStackTrace().length; i++)
	            	Logger.getAnonymousLogger().info(e.getStackTrace()[i].toString());
				iFolioid = -1;
			}

			if(iFolioid != -1) {
				java.util.regex.Pattern p = java.util.regex.Pattern.compile("'");
				java.util.regex.Matcher m = p.matcher(res);
				StringBuffer sb = new StringBuffer();
				
				while (m.find()) {
					m.appendReplacement(sb, " ");
				}
				
				m.appendTail(sb);
				res = sb.toString();
				 
				sInsert = "Insert into consultas_circulo(folioconsulta, buro, respuestaxml, consultaxml, solicitante, noetapa, valoretapa, tipocuenta, claveunidadmonetaria, importecontrato, usuario_id_sol, usuario_id_aut, computadora, confirma, sucursal, productorequerido, fecha_creacion, error) values("
					+ iFolioid + ",'B','" + res +"','" + consultaEnviada + "','" + idsol + "'," + String.valueOf(noEtapa) + ",'" + valorEtapa + "','" + TipoCuenta + "','" + ClaveUnidadMonetaria + "',"+ String.valueOf(ImporteContrato) + ","+ String.valueOf(usuario_id_sol) + "," + String.valueOf(usuario_id_aut) + ",'" + computadora + "'," + String.valueOf(confirma) + ",'" + sucursal + "'," + String.valueOf(ProductoRequerido) + ", CURRENT_TIMESTAMP, '" + error + "')";
				System.out.println(sInsert);
				st.execute(sInsert);
				Logger.getAnonymousLogger().info(sInsert);
			} 
		} catch(Exception e){
			Logger.getAnonymousLogger().info(e.toString());
	        for (int i = 0; i != e.getStackTrace().length; i++)
	        	Logger.getAnonymousLogger().info(e.getStackTrace()[i].toString());
		}

		return iFolioid;
	}
	
	private boolean actualizaError_consultacirculo(String sfolio, boolean replaceError) {
		String query = "";
		
		try {
			if (this.idError != 0L) {
				if (replaceError)
					query = "Update consultas_circulo set error = '" + this.idError + " - " + this.descError + "' , fecha_creacion = CURRENT_TIMESTAMP where folioconsulta = " + sfolio;
				else
					query = "Update consultas_circulo set error = CASE TRIM(COALESCE(error, '')) WHEN '' THEN '' ELSE COALESCE(error, '') || ' :: ' END || '" + this.idError + " - " + this.descError + "' , fecha_creacion = CURRENT_TIMESTAMP where folioconsulta = " + sfolio;
				st.execute(query);
			}
			
			if (this.idError == 0L && replaceError) {
				query = "Update consultas_circulo set error = '' , fecha_creacion = CURRENT_TIMESTAMP where folioconsulta = " + sfolio;
				st.execute(query);
			}
			
			return true;
		} catch(Exception e) {
			Logger.getAnonymousLogger().info(e.toString());
            for (int i = 0; i != e.getStackTrace().length; i++)
            	Logger.getAnonymousLogger().info(e.getStackTrace()[i].toString());
			return false;
		}
	}
	
	private int generaIdentificadorConsulta() {
		ResultSet rs = null;
		String query = "";
		int id = 0;
		
		try {
			/*SimpleDateFormat formatter = new SimpleDateFormat("HHmm");
			String value = "";
			
			value = formatter.format(Calendar.getInstance().getTime());
			
			return Integer.valueOf(value);*/
			query = "select 1 from pg_class where relkind = 'S'  and relname = 'identificadorconsulta_seq';";
			rs = st.executeQuery(query);
			if (! rs.next()) {
				query = "create sequence identificadorconsulta_seq increment 1 minvalue 1 maxvalue 9223372036854775807 start 1;";
				st.execute(query);
			}
				
			query ="SELECT COALESCE(nextval('identificadorConsulta_seq'), -1) AS id;";
			rs = st.executeQuery(query);
			if (rs.next())
				id = rs.getInt(1);
			else
				id = -1;
			
			/*if (id > 9999) {
				query = "create sequence 'identificadorConsulta_seq' increment 1 minvalue 1 maxvalue 9223372036854775807 start 1;";
			}*/
		} catch(Exception e) {
			Logger.getAnonymousLogger().info(e.toString());
            for (int i = 0; i != e.getStackTrace().length; i++)
            	Logger.getAnonymousLogger().info(e.getStackTrace()[i].toString());
			id = -1;
		}
		
		return id;
	}
	
	private String campo(String etiqueta, String value, String tipo, int maxLenght, boolean longitudFija, boolean requerido, String relleno) {
		String resRelleno = "";
		String result = "";
		int posRelleno = 0;
		
		if (value == null) {
			if (requerido) {
				for (int i = 0; i < maxLenght; i++)
					value += " ";
			} else {
				value = "";
			}
		}
		
		if ("".equals(value) && ! requerido)
			return "";
		
		if (! "".equals(value) && value.length() == maxLenght) 
			return etiqueta + this.lenghtString(value) + value;;
		
		if (relleno == null)
			relleno = "";
		
		// Truncamos el valor al maximo establecido en caso de ser necesario
		if (maxLenght > 0 && value.length() > maxLenght) {
			value = value.substring(0, maxLenght);
		}
		
		if (longitudFija) { 
			if (! "".equals(relleno)) {
				posRelleno = maxLenght - value.length();
				for (int i = 0; i < posRelleno; i++)
					resRelleno += relleno;
			}
			
			if ("N".equals(tipo) && "0".equals(relleno)) {
				result = resRelleno + value;
			} else if ("A".equals(tipo) && " ".equals(relleno)) {
				result = value + resRelleno;
			} else if ("AN".equals(tipo) && " ".equals(relleno)) {
				result = value + resRelleno;
			} else {
				result = value;
			}
		} else {
			if (! "".equals(relleno)) {
				posRelleno = maxLenght - value.length();
				for (int i = 0; i < posRelleno; i++)
					resRelleno += relleno;
			}
			
			if (! "".equals(resRelleno)) {
				if ("N".equals(tipo) && "0".equals(relleno)) {
					result = resRelleno + value;
				} else if ("A".equals(tipo) && " ".equals(relleno)) {
					result = value + resRelleno;
				} else if ("AN".equals(tipo) && " ".equals(relleno)) {
					result = value + resRelleno;
				}
			} else {
				result = value;
			}
		}
		
		return etiqueta + this.lenghtString(result) + result;
	}
	
	private String lenghtString(String value) {
		if (value.length() < 10)
			return "0" + value.length();
		return String.valueOf(value.length());
	}

	private List<String> getSegmentos(String value) {
		List<String> segmentos = new ArrayList<String>();
		
		if (value.trim().startsWith("HD")) {
			// modo automatico
			if (value.contains("\n")) {
				String[] splitted = value.split("\n");
				for (int i = 0; i < splitted.length; i++) {
					if (! "".equals(splitted[i].trim()))
						segmentos.add(splitted[i].trim());
				}	
			} else {
				
			}
		}
		
		return segmentos;
	}
	
	private HashMap<String, String> getSubsegmentos(String value) {
		HashMap<String, String> ss = new HashMap<String, String>();
		String etiqueta = "";
		String data = "";
		String lenStr = "";
		int longitud = 0;
		int pos = 0;
		
		while (value.length() > 0) {
			// obtenemos etiqueta
			etiqueta = value.substring(pos, 2);
			pos += 2;
			
			// obtenemos longitud del dato
			lenStr = value.substring(pos, pos + 2);
			longitud = Integer.parseInt(lenStr);
			pos += 2;
			
			// obtenemos el dato
			if (value.length() < (pos + longitud)) {
				data = value.substring(pos);
				ss.put(etiqueta, data);
				value = "";
			} else {
				data = value.substring(pos, pos + longitud);
				ss.put(etiqueta, data);
				value = value.substring(longitud + pos);
			}
			
			pos = 0;
		}
		
		return ss;
	}
	
	private String getFechaNacimientoFromRFC(String rfc) {
		SimpleDateFormat formatter = new SimpleDateFormat("yyMMdd");
		String fecha;
		java.util.Date dt;
		
		try {
			if (rfc.length() == 13) {
				fecha = rfc.substring(4, 10);
			} else {
				fecha = rfc.substring(3, 9);
			}
			
			dt = formatter.parse(fecha);
			formatter.applyPattern("MM/dd/yyyy");
			
			return formatter.format(dt);
		} catch (Exception ex) {
			return "null";
		}
	}
	
	private String convierteAFecha(String value) {
		return convierteAFecha(value, "ddMMyyyy", "MM/dd/yyyy");
	}
	
	private String convierteAFecha(String value, String formatoEntrada, String formatoSalida) {
		SimpleDateFormat formatter = new SimpleDateFormat(formatoEntrada);
		java.util.Date dt;
		
		try {
			if ("".equals(value)) return "null";
			dt = formatter.parse(value);
			formatter.applyPattern(formatoSalida);
			return formatter.format(dt);
		} catch (Exception ex) {
			ex.printStackTrace();
			return "null";
		}
	}
	
	private String insertQueryPersona(HashMap<String, String> segmentos, String sfolio) {
		String insert = "";
		
		insert = "Insert into circulo_personas(folioconsultaotorgante, nombres, apellidopaterno, apellidomaterno, apellidoadicional, " 
				+ "fechanacimiento, rfc, nacionalidad, estadocivil, numerodependientes, sexo, claveife) values(";
		
		// folio
		insert += sfolio;
		
		// nombre
		if (segmentos.containsKey("03") && ! "".equals(segmentos.get("03"))) {
			// segundo nombre
			if (segmentos.containsKey("04") && ! "".equals(segmentos.get("04"))) {
				insert += ", '" + segmentos.get("03") + " " + segmentos.get("04") + "'";
			} else {
				insert += ", '" + segmentos.get("03") + "'";
			}
		} else {
			insert += ", null";
		}
		
		// apellido paterno
		if (segmentos.containsKey("05") && ! "".equals(segmentos.get("05")) && ! "NO PROPORCIONADO".equals(segmentos.get("05"))) {
			insert += ", '" + segmentos.get("05") + "'";
		} else {
			insert += ", null";
		}
		
		// apellido paterno
		if (segmentos.containsKey("05") && ! "".equals(segmentos.get("05")) && ! "NO PROPORCIONADO".equals(segmentos.get("05"))) {
			insert += ", '" + segmentos.get("05") + "'";
		} else {
			insert += ", null";
		}
		
		// apellido adicional
		/*if (segmentos.containsKey("06") && ! "".equals(segmentos.get("06")) && ! "NO PROPORCIONADO".equals(segmentos.get("06"))) {
			insert += ", '" + segmentos.get("06") + "'";
		} else {
			insert += ", null";
		}*/
		
		// apellido adicional
		insert += ", null";
		
		// fecha nacimiento
		if (segmentos.containsKey("01") && ! "".equals(segmentos.get("01"))) {
			insert += ", '" + getFechaNacimientoFromRFC(segmentos.get("01")) + "'";
		} else {
			insert += ", null";
		}
		
		// rfc
		if (segmentos.containsKey("01") && ! "".equals(segmentos.get("01"))) {
			insert += ", '" + segmentos.get("01") + "'";
		} else {
			insert += ", null";
		}
		
		// nacionalidad
		if (segmentos.containsKey("18") && ! "".equals(segmentos.get("18"))) {
			insert += ", '" + segmentos.get("18") + "'";
		} else {
			insert += ", null";
		}
		
		// curp
		//insert += ", null";
		
		// residencia
		//insert += ", null";
		
		// numero de licencia
		//insert += ", null";
		
		// estado civil
		insert += ", null";
		
		// numero dependientes
		insert += ", null";
		
		// sexo
		insert += ", null";
		
		// ife
		insert += ", null";
		
		// fin de insert
		insert += ");";
		
		return insert;
	}
	
	private String insertQueryDomicilio(HashMap<String, String> segmentos, String sfolio) {
		String insert = "Insert into circulo_domicilios(folioconsultaotorgante, direccion, ciudad, estado, cp, fecharesidencia, telefono, " 
				+ "delegacionmunicipio, coloniapoblacion, fecharegistrodomicilio) values(";

		// folio
		insert += sfolio;
		
		// direccion 1 y 2
		if (segmentos.containsKey("07") && ! "".equals(segmentos.get("07"))) {
			if (segmentos.containsKey("08") && ! "".equals(segmentos.get("08"))) {
				insert += ", '" + segmentos.get("07") + " " + segmentos.get("08") + "'";
			} else {
				insert += ", '" + segmentos.get("07") + "'";
			}
		} else {
			insert += ", null";
		}
		
		// ciudad
		if (segmentos.containsKey("11") && ! "".equals(segmentos.get("11"))) {
			insert += ", '" + segmentos.get("11") + "'";
		} else {
			insert += ", null";
		}
		
		// estado
		if (segmentos.containsKey("12") && ! "".equals(segmentos.get("12"))) {
			insert += ", '" + segmentos.get("12") + "'";
		} else {
			insert += ", null";
		}
		
		// codigo postal
		if (segmentos.containsKey("13") && ! "".equals(segmentos.get("13"))) {
			insert += ", '" + segmentos.get("13") + "'";
		} else {
			insert += ", null";
		}
		
		// fecha residencia
		insert += ", null";
		/*if (segmentos.containsKey("12") && ! "".equals(segmentos.get("12"))) {
			insert += ", '" + segmentos.get("12") + "'";
		} else {
			insert += ", null";
		}*/
		
		// telefono
		if (segmentos.containsKey("15") && ! "".equals(segmentos.get("15"))) {
			insert += ", '" + segmentos.get("15") + "'";
		} else {
			insert += ", null";
		}
		
		// delegacion o municipio
		if (segmentos.containsKey("10") && ! "".equals(segmentos.get("10"))) {
			insert += ", '" + segmentos.get("10") + "'";
		} else {
			insert += ", null";
		}
		
		// colonia
		if (segmentos.containsKey("09") && ! "".equals(segmentos.get("09"))) {
			insert += ", '" + segmentos.get("09") + "'";
		} else {
			insert += ", null";
		}
		
		// fecha registro domicilio
		insert += ", null";
		
		// fin de insert
		insert += ");";
		
		return insert;
	}
	
	private String insertQueryCuentas(HashMap<String, String> segmentos, String sfolio) {
		String insert = "Insert into circulo_cuentas(folioconsultaotorgante, fechaactualizacion, registroimpugnado, claveotorgante, nombreotorgante, " +
				"tiporesponsabilidad, tipocuenta, numeropagos, frecuenciapagos, fechaaperturacuenta, fechaultimopago, fechacierrecuenta, " +
				"saldoactual, saldovencido, numeropagosvencidos, historicopagos,claveunidadmonetaria, pagoactual, fechapeoratraso," +
				"saldovencidopeoratraso,limitecredito, montopagar, tipocredito, peoratraso, observacion, creditomaximo, fechaultimacompra, " +
				"fecharecientehistoricopagos, fechaantiguahistoricopagos, tipo_credito_id) values(";
		
		// folio
		insert += sfolio;
		
		// fecha actualizacion
		if (segmentos.containsKey("17") && ! "".equals(segmentos.get("17"))) {
			insert += ", '" + convierteAFecha(segmentos.get("17"), "yyyyMM", "yyyy-MM-dd") + "'";
		} else {
			insert += ", null";
		}
		
		// registro impugnado
		if (segmentos.containsKey("25") && ! "".equals(segmentos.get("25"))) {
			insert += ", '" + segmentos.get("25") + "'";
		} else {
			insert += ", ''";
		}
		
		// clave otorgante
		if (segmentos.containsKey("01") && ! "".equals(segmentos.get("01"))) {
			insert += ", '" + segmentos.get("01") + "'";
		} else {
			insert += ", ''";
		}
		
		// nombre otorgante
		if (segmentos.containsKey("02") && ! "".equals(segmentos.get("02"))) {
			insert += ", '" + segmentos.get("02") + "'";
		} else {
			insert += ", ''";
		}
		
		// tipo responsabilidad
		/*if (segmentos.containsKey("CR") && ! "".equals(segmentos.get("CR"))) {
			insert += ", '" + segmentos.get("CR") + "'";
		} else {
			insert += ", ''";
		}*/
		insert += ", ''";

		// tipo cuenta
		/*if (segmentos.containsKey("CR") && ! "".equals(segmentos.get("CR"))) {
			insert += ", '" + segmentos.get("CR") + "'";
		} else {
			insert += ", ''";
		}*/
		insert += ", ''";
		
		// numeropagos
		if (segmentos.containsKey("27") && ! "".equals(segmentos.get("27"))) {
			insert += ", " + segmentos.get("27");
		} else {
			insert += ", 0";
		}
		
		// frecuenciapagos
		if (segmentos.containsKey("28") && ! "".equals(segmentos.get("28"))) {
			insert += ", '" + segmentos.get("28") + "'";
		} else {
			insert += ", null";
		}

		// fechaaperturacuenta
		if (segmentos.containsKey("05") && ! "".equals(segmentos.get("05"))) {
			insert += ", '" + this.convierteAFecha(segmentos.get("05"), "ddMMyyyy", "MM/dd/yyyy") + "'";
		} else {
			insert += ", null";
		}

		// fechaultimopago
		if (segmentos.containsKey("30") && ! "".equals(segmentos.get("30"))) {
			insert += ", '" + this.convierteAFecha(segmentos.get("30"), "ddMMyyyy", "MM/dd/yyyy") + "'";
		} else {
			insert += ", null";
		}

		// fechacierrecuenta
		if (segmentos.containsKey("18") && ! "".equals(segmentos.get("18"))) {
			insert += ", '" + this.convierteAFecha(segmentos.get("18"), "ddMMyyyy", "MM/dd/yyyy") + "'";
		} else {
			insert += ", null";
		}

		// saldoactual
		if (segmentos.containsKey("10") && ! "".equals(segmentos.get("10"))) {
			insert += ", " + segmentos.get("10");
		} else {
			insert += ", 0";
		}

		// saldovencido
		if (segmentos.containsKey("11") && ! "".equals(segmentos.get("11"))) {
			insert += ", " + segmentos.get("11");
		} else {
			insert += ", 0";
		}

		// numeropagosvencidos
		/*if (segmentos.containsKey("CR") && ! "".equals(segmentos.get("CR"))) {
			insert += ", '" + segmentos.get("CR") + "'";
		} else {
			insert += ", ''";
		}*/
		insert += ", 0";

		// historicopagos
		if (segmentos.containsKey("23") && ! "".equals(segmentos.get("23"))) {
			insert += ", '" + segmentos.get("23") + "'";
		} else {
			insert += ", ''";
		}

		// claveunidadmonetaria
		if (segmentos.containsKey("04") && ! "".equals(segmentos.get("04"))) {
			if ("001".equals(segmentos.get("04")))
				insert += ", 'MX'";
			else
				insert += ", '" + segmentos.get("04") + "'";
		} else {
			insert += ", ''";
		}

		// pagoactual
		/*if (segmentos.containsKey("CR") && ! "".equals(segmentos.get("CR"))) {
			insert += ", '" + segmentos.get("CR") + "'";
		} else {
			insert += ", ''";
		}*/
		insert += ", ''";

		// fechapeoratraso
		/*if (segmentos.containsKey("CR") && ! "".equals(segmentos.get("CR"))) {
			insert += ", '" + segmentos.get("CR") + "'";
		} else {
			insert += ", ''";
		}*/
		insert += ", null";

		// saldovencidopeoratraso
		if (segmentos.containsKey("11") && ! "".equals(segmentos.get("11"))) {
			double valor = 0;
			if (segmentos.containsKey("12") && ! "".equals(segmentos.get("12")))
				valor = Double.parseDouble(segmentos.get("12"));
			
			if (segmentos.containsKey("13") && ! "".equals(segmentos.get("13"))) 
				valor = Double.parseDouble(segmentos.get("13"));
			
			if (segmentos.containsKey("14") && ! "".equals(segmentos.get("14"))) 
				valor = Double.parseDouble(segmentos.get("14"));
			
			if (segmentos.containsKey("15") && ! "".equals(segmentos.get("15"))) 
				valor = Double.parseDouble(segmentos.get("15"));
			
			if (segmentos.containsKey("16") && ! "".equals(segmentos.get("16"))) 
				valor = Double.parseDouble(segmentos.get("16"));
			
			insert += ", '" + valor + "'";
		} else {
			insert += ", null";
		}

		// limitecredito
		/*if (segmentos.containsKey("CR") && ! "".equals(segmentos.get("CR"))) {
			insert += ", " + segmentos.get("CR");
		} else {
			insert += ", 0";
		}*/
		insert += ", 0";

		// montopagar
		if (segmentos.containsKey("29") && ! "".equals(segmentos.get("29"))) {
			insert += ", " + segmentos.get("29");
		} else {
			insert += ", 0";
		}

		// tipocredito
		if (segmentos.containsKey("09") && ! "".equals(segmentos.get("09"))) {
			insert += ", '" + this.getTipoCredito(segmentos.get("09"), false) + "'";
		} else {
			insert += ", ''";
		}

		// peoratraso
		/*if (segmentos.containsKey("CR") && ! "".equals(segmentos.get("CR"))) {
			insert += ", '" + segmentos.get("CR") + "'";
		} else {
			insert += ", ''";
		}*/
		insert += ", ''";

		// observacion
		if (segmentos.containsKey("08") && ! "".equals(segmentos.get("08"))) {
			insert += ", '" + segmentos.get("08") + "'";
		} else {
			insert += ", null";
		}

		// creditomaximo
		if (segmentos.containsKey("34") && ! "".equals(segmentos.get("34"))) {
			insert += ", " + segmentos.get("34");
		} else {
			insert += ", 0";
		}

		// fechaultimacompra
		/*if (segmentos.containsKey("CR") && ! "".equals(segmentos.get("CR"))) {
			insert += ", '" + segmentos.get("CR") + "'";MM/dd/yyyy
		} else {
			insert += ", ''";
		}*/
		insert += ", null";

		// fecharecientehistoricopagos
		/*if (segmentos.containsKey("CR") && ! "".equals(segmentos.get("CR"))) {
			insert += ", '" + segmentos.get("CR") + "'";
		} else {
			insert += ", ''";
		}*/
		insert += ", null";

		// fechaantiguahistoricopagos
		/*if (segmentos.containsKey("CR") && ! "".equals(segmentos.get("CR"))) {
			insert += ", '" + segmentos.get("CR") + "'";
		} else {
			insert += ", ''";
		}*/
		insert += ", null";

		// tipo_credito_id
		/*if (segmentos.containsKey("CR") && ! "".equals(segmentos.get("CR"))) {
			insert += ", '" + segmentos.get("CR") + "'";
		} else {
			insert += ", null";
		}*/
		insert += ", null";
		
		insert += ");";

				
		return insert;
	}
	
	private String insertQueryConsultasEfectuadas(HashMap<String, String> segmentos, String sfolio) {
		String insert = "Insert into circulo_consultas_efectuadas(folioconsultaotorgante, fechaconsulta, claveotorgante, nombreotorgante, " 
				+ "tipocredito, importecredito, tiporesponsabilidad, claveunidadmonetaria) values(";
		
		// folio
		insert += sfolio;
		
		// fechaconsulta
		if (segmentos.containsKey("NONE") && ! "".equals(segmentos.get("NONE"))) {
			insert += ", '" + segmentos.get("NONE") + "'";
		} else {
			insert += ", null";
		}
		
		// claveotorgante
		if (segmentos.containsKey("NONE") && ! "".equals(segmentos.get("NONE"))) {
			insert += ", '" + segmentos.get("NONE") + "'";
		} else {
			insert += ", null";
		}
		
		// nombreotorgante
		if (segmentos.containsKey("NONE") && ! "".equals(segmentos.get("NONE"))) {
			insert += ", '" + segmentos.get("NONE") + "'";
		} else {
			insert += ", null";
		}
		
		// tipocredito
		if (segmentos.containsKey("NONE") && ! "".equals(segmentos.get("NONE"))) {
			insert += ", '" + segmentos.get("NONE") + "'";
		} else {
			insert += ", null";
		}
		
		// importecredito
		if (segmentos.containsKey("NONE") && ! "".equals(segmentos.get("NONE"))) {
			insert += ", '" + segmentos.get("NONE") + "'";
		} else {
			insert += ", null";
		}
		
		// tiporesponsabilidad
		if (segmentos.containsKey("NONE") && ! "".equals(segmentos.get("NONE"))) {
			insert += ", '" + segmentos.get("NONE") + "'";
		} else {
			insert += ", null";
		}
		
		// claveunidadmonetaria
		if (segmentos.containsKey("NONE") && ! "".equals(segmentos.get("NONE"))) {
			insert += ", '" + segmentos.get("NONE") + "'";
		} else {
			insert += ", null";
		}
		
		insert += ");";
		
		return insert;
	}
	
	public long getIdError() {
		return idError;
	}

	public void setIdError(long idError) {
		this.idError = idError;
	}

	public String getDescError() {
		return descError;
	}

	public void setDescError(String descError) {
		this.descError = descError;
	}
	
	private boolean cleanFolio(String folio) {
		String query = "";
		
		try {
			query = "DELETE FROM circulo_personas WHERE folioconsultaotorgante = " + folio;
			this.st.execute(query);
		    
		    query = "DELETE FROM circulo_domicilios WHERE folioconsultaotorgante = " + folio;
		    this.st.execute(query);
		    
		    query = "DELETE FROM circulo_cuentas WHERE folioconsultaotorgante = " + folio;
		    this.st.execute(query);
		    
		    query = "DELETE FROM circulo_empleos WHERE folioconsultaotorgante = " + folio;
		    this.st.execute(query);
		    
		    query = "DELETE FROM circulo_consultas_efectuadas WHERE folioconsultaotorgante = " + folio;
		    this.st.execute(query);
		    
		    // Exclusivas PERSONA MORAL
			query = "DELETE FROM buro_consultapm_hd WHERE folio_consulta = " + folio;
			this.st.execute(query);

			query = "DELETE FROM buro_consultapm_em WHERE folio_consulta = " + folio;
			this.st.execute(query);

			query = "DELETE FROM buro_consultapm_hc WHERE folio_consulta = " + folio;
			this.st.execute(query);

			query = "DELETE FROM buro_consultapm_hr WHERE folio_consulta = " + folio;
			this.st.execute(query);

			query = "DELETE FROM buro_consultapm_dc WHERE folio_consulta = " + folio;
			this.st.execute(query);

			query = "DELETE FROM buro_consultapm_ac WHERE folio_consulta = " + folio;
			this.st.execute(query);

			query = "DELETE FROM buro_consultapm_cr WHERE folio_consulta = " + folio;
			this.st.execute(query);

			query = "DELETE FROM buro_consultapm_hi WHERE folio_consulta = " + folio;
			this.st.execute(query);

			query = "DELETE FROM buro_consultapm_co WHERE folio_consulta = " + folio;
			this.st.execute(query);

			query = "DELETE FROM buro_consultapm_fd WHERE folio_consulta = " + folio;
			this.st.execute(query);

			query = "DELETE FROM buro_consultapm_cn WHERE folio_consulta = " + folio;
			this.st.execute(query);

			query = "DELETE FROM buro_consultapm_er WHERE folio_consulta = " + folio;
			this.st.execute(query);

			query = "DELETE FROM buro_consultapm_ci WHERE folio_consulta = " + folio;
			this.st.execute(query);

			return true;
		} catch (Exception e) {
			this.idError = -4L;
			this.descError = "Error al limpiar el folio de consulta - " + e.getMessage();
			return false;
		}
	}
	
	// Anexo 1
	@SuppressWarnings("unused")
	private String getDescripcionTipoUsuario(String codigo) {
		if ("001".equals(codigo))
			return "Banco";
		if ("002".equals(codigo))
			return "Arrendadora";
		if ("003".equals(codigo))
			return "Unión de Crédito";
		if ("004".equals(codigo))
			return "Factoraje";
		if ("005".equals(codigo))
			return "Otras Financieras";
		if ("007".equals(codigo))
			return "Almacenadoras";
		if ("008".equals(codigo))
			return "Fondos y Fideicomisos";
		if ("009".equals(codigo))
			return "Seguros";
		if ("010".equals(codigo))
			return "Fianzas";
		if ("011 ".equals(codigo))
			return "Caja de Ahorro";
		if ("012".equals(codigo))
			return "Gobierno";
		if ("013".equals(codigo))
			return "Administradora de Cartera";
		if ("014".equals(codigo))
			return "Sociedad de Información Crediticia";
		if ("015".equals(codigo))
			return "Comunicaciones";
		if ("016".equals(codigo))
			return "Servicios";
		if ("999".equals(codigo))
			return "Comercial";
		return "";
	}
	
	// Anexo 2
	private String getTipoCredito(String value, boolean nombreGenerico) {
		if ("1300".equals(value))
			return ((! nombreGenerico) ? "Cartera de Arrendamiento Puro y Créditos " : "ARREN PURO");
		if ("1301".equals(value))
			return ((! nombreGenerico) ? "Descuentos " : "DESCUENTOS");
		if ("1302".equals(value))
			return ((! nombreGenerico) ? "Quirografario " : "QUIROG");
		if ("1303".equals(value))
			return ((! nombreGenerico) ? "Con Colateral " : "COLATERAL");
		if ("1304".equals(value))
			return ((! nombreGenerico) ? "Prendario " : "PRENDAR");
		if ("1305".equals(value))
			return ((! nombreGenerico) ? "Créditos simples y créditos en cuenta corriente " : "SIMPLE");
		if ("1306".equals(value))
			return ((! nombreGenerico) ? "Préstamos con garantía de unidades industriales " : "P.G.U.I.");
		if ("1307".equals(value))
			return ((! nombreGenerico) ? "Créditos de habilitación o avío " : "HABILITACION");
		if ("1308".equals(value))
			return ((! nombreGenerico) ? "Créditos Refaccionarios " : "REFACC");
		if ("1309".equals(value))
			return ((! nombreGenerico) ? "Prestamos Inmobil Emp Prod de Bienes o Servicios " : "I.E.P.B.S.");
		if ("1310".equals(value))
			return ((! nombreGenerico) ? "Préstamos para la vivienda " : "VIVIENDA");
		if ("1311".equals(value))
			return ((! nombreGenerico) ? "Otros créditos con garantía inmobiliaria " : "O.C. GARANTIA INMOB");
		if ("1314".equals(value))
			return ((! nombreGenerico) ? "No Disponible " : "NO DISPONIBLE");
		if ("1316".equals(value))
			return ((! nombreGenerico) ? "Otros adeudos vencidos " : "O.A.V.");
		if ("1317".equals(value))
			return ((! nombreGenerico) ? "Créditos venidos a menos aseg. Gtias. Adicionales " : "C.V.A.");
		if ("1320".equals(value))
			return ((! nombreGenerico) ? "Cartera de Arrendamiento Financiero Vigente " : "ARREN VIGENTE");
		if ("1321".equals(value))
			return ((! nombreGenerico) ? "Cartera de Arrendamiento Financiero Sindicado con Aportación " : "ARREN SINDICADO");
		if ("1322".equals(value))
			return ((! nombreGenerico) ? "Crédito de Arrendamiento " : "ARREND");
		if ("1323".equals(value))
			return ((! nombreGenerico) ? "Créditos Reestructurados " : "REESTRUCTURADOS");
		if ("1324".equals(value))
			return ((! nombreGenerico) ? "Créditos Renovados " : "RENOVADOS");
		if ("1327".equals(value))
			return ((! nombreGenerico) ? "Arrendamiento Financiero Sindicado " : "ARR. FINAN. SINDICADO");
		if ("1340".equals(value))
			return ((! nombreGenerico) ? "Cartera descontada con Inst. de Crédito " : "REDESCUENTO");
		if ("1341".equals(value))
			return ((! nombreGenerico) ? "Redescuento otra cartera descontada " : "O. REDESCUENTO");
		if ("1342".equals(value))
			return ((! nombreGenerico) ? "Redescuento, cartera de crédito reestructurado mediante su descuento en programas Fidec. " : "RED. REESTRUCTURADOS");
		if ("1350".equals(value))
			return ((! nombreGenerico) ? "Prestamos con Fideicomisos de Garantía " : "PRESTAMOS C/FIDEICOMISOS GARANTÍA");
		if ("1380".equals(value))
			return ((! nombreGenerico) ? "Tarjeta de Crédito empresarial / Tarjeta Corporativa " : "T. CRED. EMPRESARIAL-CORPORATIVA");
		if ("2303".equals(value))
			return ((! nombreGenerico) ? "Cartas de Crédito " : "CARTAS DE CREDITO");
		if ("3011".equals(value))
			return ((! nombreGenerico) ? "Cartera de Factoraje con Recursos " : "FACTORAJE C/REC");
		if ("3012".equals(value))
			return ((! nombreGenerico) ? "Cartera de Factoraje sin Recursos " : "FACTORAJE S/REC");
		if ("3230".equals(value))
			return ((! nombreGenerico) ? "Anticipo a Clientes Por Promesa de Factoraje " : "ANT.A.C.P.P.FACTORAJE");
		if ("3231".equals(value))
			return ((! nombreGenerico) ? "Cartera de Arrendamiento Financiero Vigente " : "ARREN VIGENTE");
		if ("6103".equals(value))
			return ((! nombreGenerico) ? "Adeudos por Aval " : "ADEUDOS POR AVAL");
		if ("6105".equals(value))
			return ((! nombreGenerico) ? "Cartas de Créditos No Dispuestas " : "CARTAS DE CRÉDITOS NO DISPUESTAS");
		if ("6228".equals(value))
			return ((! nombreGenerico) ? "Fideicomisos Programa de apoyo crediticio a la planta productiva Nacional en Udis " : "FIDEICOMISOS PLANTA PRODUCTIVA");
		if ("6229".equals(value))
			return ((! nombreGenerico) ? "Fideicomisos Programa de apoyo crediticio a los Estados y Municipios UDIS " : "FIDEICOMISOS EDOS");
		if ("6230".equals(value))
			return ((! nombreGenerico) ? "Fideicomisos Programa de apoyo para deudores de créditos de Vivienda UDIS " : "FIDEICOMISOS VIVIENDA");
		if ("6240".equals(value))
			return ((! nombreGenerico) ? "Aba Pasem II " : "ABA PASEM II");
		if ("6250".equals(value))
			return ((! nombreGenerico) ? "Tarjeta de Servicio " : "TARJETA DE SERVICIO");
		if ("6260".equals(value))
			return ((! nombreGenerico) ? "Crédito Fiscal " : "CRÉDITO FISCAL");
		if ("6270".equals(value))
			return ((! nombreGenerico) ? "Crédito Automotriz " : "CRÉDITO AUTOMOTRIZ");
		if ("6280".equals(value))
			return ((! nombreGenerico) ? "Línea de Crédito " : "LÍNEA DE CRÉDITO");
		if ("6290".equals(value))
			return ((! nombreGenerico) ? "Seguros " : "SEGUROS");
		if ("6291".equals(value))
			return ((! nombreGenerico) ? "Fianzas " : "FIANZAS");
		if ("6292".equals(value))
			return ((! nombreGenerico) ? "Fondos y Fideicomisos " : "FONDOS Y FIDEICOMISOS");
		
		return "";
	}
	
	// Anexo 4
	@SuppressWarnings("unused")
	private String getNombreClaveObservacion(String clave) {
		return "";
	}
	
	// Anexo 6
	@SuppressWarnings("unused")
	private String getDescripcionClavePrevencion(String clave) {
		if ("78".equals(clave))
			return "Negocio receptor de tarjetas de crédito que ocasionó pérdida al Usuario";
		if ("79".equals(clave))
			return "Persona relacionada con la empresa o con Persona Física con Actividad Empresarial con clave de prevención";
		if ("80".equals(clave))
			return "Cliente declarado en quiebra, suspensión de pagos o en concurso mercantil";
		if ("81".equals(clave))
			return "Cliente en trámite judicial";
		if ("82".equals(clave))
			return "Cliente que propició pérdida al Otorgante por fraude comprobado, declarado conforme a sentencia judicial";
		if ("83".equals(clave))
			return "Cliente que solicitó y/o acordó con el Otorgante liquidación del crédito con pago menor a la deuda total";
		if ("84".equals(clave))
			return "El Usuario no ha podido localizar al Cliente, titular de la cuenta";
		if ("85".equals(clave))
			return "Cliente desvió recursos a fines distintos a los pactados, debidamente comprobado";
		if ("86".equals(clave))
			return "Cliente que dispuso de las garantías que respaldan el crédito sin autorización del Otorgante";
		if ("87".equals(clave))
			return "Cliente que enajena o cambia régimen de propiedad de sus bienes o permite gravámenes sobre los mismos";
		if ("88".equals(clave))
			return "Cliente que dispuso de las retenciones de sus trabajadores, no enterando a la Institución correspondiente";
		if ("92".equals(clave))
			return "Cliente que propició pérdida total al Otorgante";
		
		return "";
	}
	
	// Anexo 7
	@SuppressWarnings("unused")
	private String getDescripcionHistoricoPagos(String codigo) {
		if ("D".equals(codigo))
			return "Información anulada a solicitud del Usuario";
		if ("-".equals(codigo))
			return "Período no reportado por el Usuario";
		if ("1".equals(codigo))
			return "Cuenta al corriente, 0 días de atraso de su fecha límite de pago";
		if ("2".equals(codigo))
			return "Cuenta con atraso de 1 a 29 días de su fecha límite de pago";
		if ("3".equals(codigo))
			return "Cuenta con atraso de 30 a 59 días de su fecha límite de pago";
		if ("4".equals(codigo))
			return "Cuenta con atraso de 60 a 89 días de su fecha límite de pago";
		if ("5".equals(codigo))
			return "Cuenta con atraso de 90 a 119 días de su fecha límite de pago";
		if ("6".equals(codigo))
			return "Cuenta con atraso de 120 a 179 días de su fecha límite de pago";
		if ("7".equals(codigo))
			return "Cuenta con atraso de 180 días o más de su fecha límite de pago";
		if (" ".equals(codigo))
			return "Periodo eliminado por el Usuario en razón de aplicación de la Ley para Regular a las Sociedad de Información Crediticia";

		return "";
	}
	
	// Anexo 8 
	@SuppressWarnings("unused")
	private String getDescripcionMoneda(String codigo) {
		return "";
	}
	
	// Anexo 9
	@SuppressWarnings("unused")
	private String getDescripcionPaises(String codigo) {
		return "";
	}
	
	// Anexo 10
	@SuppressWarnings("unused")
	private String getDescripcionCodigoEstados(String codigo) {
		return "";
	}
	
	// Anexo 11
	private String getNombreClaveCalifica(String claveCalifica) {
		if ("0".equals(claveCalifica))
			return "BK12_CLEAN";
		if ("1".equals(claveCalifica))
			return "BK12_NUM_CRED";
		if ("2".equals(claveCalifica))
			return "BK12_NUM_TC_ACT";
		if ("3".equals(claveCalifica))
			return "NBK12_NUM_CRED";
		if ("4".equals(claveCalifica))
			return "BK12_NUM_EXP_PAIDONTIME";
		if ("5".equals(claveCalifica))
			return "BK12_PCT_PROMT";
		if ("6".equals(claveCalifica))
			return "NBK12_PCT_PROMT";
		if ("7".equals(claveCalifica))
			return "BK12_PCT_SAT";
		if ("8".equals(claveCalifica))
			return "NBK12_PCT_SAT";
		if ("9".equals(claveCalifica))
			return "BK24_PCT_60PLUS";
		if ("10".equals(claveCalifica))
			return "NBK24_PCT_60PLUS";
		if ("11".equals(claveCalifica))
			return "NBK12_COMM_PCT_PLUS";
		if ("12".equals(claveCalifica))
			return "BK12_PCT_90PLUS";
		if ("13".equals(claveCalifica))
			return "BK12_DPD_PROM";
		if ("14".equals(claveCalifica))
			return "BK12_IND_QCRA";
		if ("15".equals(claveCalifica))
			return "BK12_MAX_CREDIT_AMT";
		if ("16".equals(claveCalifica))
			return "MONTHS_ON_FILE_BANKING";
		if ("17".equals(claveCalifica))
			return "MONTHS_SINCE_LAST_OPEN_BANKING";
		if ("18".equals(claveCalifica))
			return "BK_IND_PMOR";
		if ("19".equals(claveCalifica))
			return "BK24_IND_EXP";
		if ("20".equals(claveCalifica))
			return "12_INST";
		if ("21".equals(claveCalifica))
			return "BK_DEUDA_TOT";
		if ("22".equals(claveCalifica))
			return "BK_DEUDA_CP";
		if ("23".equals(claveCalifica))
			return "NBK_DEUDA_TOT";
		if ("24".equals(claveCalifica))
			return "NBK_DEUDA_CP";
		if ("25".equals(claveCalifica))
			return "DEUDA_TOT";
		if ("26".equals(claveCalifica))
			return "DEUDA_TOT_CP";
		
		return "";
	}
}

//HISTORIAL DE MODIFICACIONES
//---------------------------------------------------------------
//VERSIÓN |   FECHA    |     AUTOR      | DESCRIPCIÓN
//---------------------------------------------------------------
//   2.1  | 30/09/2016 | Miguel Aguilar | Se agregó un replace a la cadena de consulta para que cambie las letras Ñ por N.
//   2.1  | 03/10/2016 | Miguel Aguilar | Se cambió el valor de la etiqueta que contiene el valor correspondiente a la firma de autorización del cliente.
//   2.1  | 26/10/2016 | Miguel Aguilar | Se agregó una forma de interpretar los errores que son devueltos como respuesta desde buro.