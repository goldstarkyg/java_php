package net.sistacc.ws.buro;
// java -cp gson-2.2.4.jar:postgresql-8.2-510.jdbc3.jar:.  net.asp.buro.BuroPFAutenticador


/*
 http://192.168.11.251:7080/WSBuro/servlet/RespuestaPF?folio=22809
 http://192.168.11.251:7080/WSBuro/servlet/ConsultaPF?solicitante=27259&noEtapa=1&valorEtapa=1&ProductoRequerido=1&TipoCuenta=1&ClaveUnidadMonetaria=1&ImporteContrato=10&usuario_id_sol=0&usuario_id_aut=0&computadora=servdior&confirma=1&sucursal=1
 */

import java.io.DataInputStream;
import java.io.DataOutputStream;
import java.net.Socket;
import java.sql.Connection;
import java.sql.ResultSet;
import java.sql.Statement;
import  java.util.logging.Logger;
 
public class BuroPFAutenticador  {

	public int consulta(Connection conn, String Solicitante,
			String ClaveUnidadMonetaria,
			double ImporteContrato, 
			int usuario_id_sol,
			int usuario_id_aut, 
			String computadora, 
			int confirma, 
			String sucursal, 
			String sValores) {
		int folio = -1;
		int noEtapa = 1;
		String valorEtapa = "";
		String xConsulta = "";
		String TipoCuenta = "";
		int ProductoRequerido = 1;
		DataOutputStream out = null;
	    DataInputStream in = null;		
		String host = "128.9.55.108";
		Socket buroSocket;
		String xrespuestaBuro  = "";
		try {
			try{
				st = conn.createStatement();
				//xConsulta = cadenaconsulta(Solicitante, (int)ImporteContrato);
				//xConsulta = INTFConsulta.generaINTF("{tarjetaTiene: \"F\", hipotecarioTiene: \"F\", automotrizTiene: \"F\",  rfc: \"MELA740205\", referencia: \"1234567980\", tipoCuenta: \"F\", cveMoneda: \"MX\", numFirma: 12345, mtoContrato: 90000, aPaterno: \"MEZA\", aMaterno: \"LIZARDI\", nombre: \"AUGUSTO\", fecNacimiento: \"05021974\", domicilio: \"AVE DELFINES SN\", colonia: \"fidepaz\", municipio: \"La pAz\", estado: \"bcs\", cp:\"23090\", telefono: \"1236250\"}");
				//xConsulta = INTFConsulta.generaINTF("{tarjetaTiene: \"V\", tarjetaNum: \"6856\", hipotecarioTiene: \"F\", automotrizTiene: \"F\",  rfc: \"ROCG750601\", referencia: \"GRALCERTIF66\", tipoCuenta: \"F\", cveMoneda: \"MX\", numFirma: 12345, mtoContrato: 90000, aPaterno: \"ROMERO\", aMaterno: \"CUENTO\", nombre: \"GILBERTO\", fecNacimiento: \"01061975\", domicilio: \"GIRASOLES 32 INT 3\", colonia: \"SAN FRANCISCO\", municipio: \"TLALPAN\", estado: \"DF\", cp:\"10810\"}");
				//xConsulta = "INTL11GRALCERTIF66             001MX0000ZM27341006OAnuXfw3IUKMX000000000SP01     0000000PN06ROMERO0006CUENTO0208GILBERTO0408010619750510ROCG750601PA18GIRASOLES 32 INT 30113SAN FRANCISCO0207TLALPAN0306MEXICO0402DF050510810AU03RCN000110125GRALCERTIF66             0201V040468560701F1101FES05000010002**";
				//renglon 2: ANGEL	MENDEZ	ANGELES	10/01/1977	MEAA771001	TFA	2 DE ABR 108	MANUEL AVILA CAMACHO	(null)	(null)	POZA RICA	VER 	933	0		8097	1/09/2004	1/01/1900	R	CC	6000
				//xConsulta =  INTFConsulta.generaINTF("{nombre: \"ANGEL\", aPaterno: \"MENDEZ\", aMaterno: \"ANGELES\", fecNacimiento: \"10011977\", rfc: \"MEAA771001TFA\", domicilio: \"2 DE ABR 108 MANUEL AVILA CAMACHO\", colonia: \"\", municipio: \"\", ciudad: \"POZA RICA\", estado: \"VER\", cp:\"93300\", tarjetaTiene: \"V\", tarjetaNum: \"8097\", hipotecarioTiene: \"F\", automotrizTiene: \"F\",  referencia: \"GRALCERTIF66\", responsabilidad: \"R\", contrato: \"CC\", cveMoneda: \"MX\", numFirma: 12345, mtoContrato: 6000}");
				//renglon 10: ADA	AVILA	FUENTES	27/12/1958	AIFA581227	UBA	IGNACIO LOPEZ RAYON NO 11	(null)	MANUEL AVILA CAMACHO	POZA RICA DE HIDALGO	(null)	VER 	932	20		8183	1/02/2005	1/01/1900	R	CC	6500
				//xConsulta =  INTFConsulta.generaINTF("{nombre: \"ADA\", aPaterno: \"AVILA\", aMaterno: \"FUENTES\", fecNacimiento: \"27121958\", rfc: \"AIFA581227UBA\", domicilio: \"IGNACIO LOPEZ RAYON NO 11\", colonia: \"MANUEL AVILA CAMACHO\", municipio: \"POZA RICA DE HIDALGO\", ciudad: \"\", estado: \"VER\", cp:\"93220\", tarjetaTiene: \"V\", tarjetaNum: \"8183\", hipotecarioTiene: \"F\", automotrizTiene: \"F\",  referencia: \"GRALCERTIF66\", responsabilidad: \"R\", contrato: \"CC\", cveMoneda: \"MX\", numFirma: 12345, mtoContrato: 6500}");
				/*//renglon 12: ADA	AVILA	FUENTES	27/12/1958	AIFA581227	UBA	IGNACIO LOPEZ RAYON NO 11	(null)	MANUEL AVILA CAMACHO	POZA RICA DE HIDALGO	(null)	VER 	932	20		9582	10/03/2005	1/01/1900	R	CC	6000
				xConsulta =  INTFConsulta.generaINTF("{nombre: \"ADA\", aPaterno: \"AVILA\", aMaterno: \"FUENTES\", fecNacimiento: \"27121958\", rfc: \"AIFA581227UBA\", domicilio: \"IGNACIO LOPEZ RAYON NO 11\", colonia: \"MANUEL AVILA CAMACHO\", municipio: \"POZA RICA DE HIDALGO\", ciudad: \"\", estado: \"VER\", cp:\"93220\", tarjetaTiene: \"V\", tarjetaNum: \"9582\", hipotecarioTiene: \"F\", automotrizTiene: \"F\",  referencia: \"GRALCERTIF66\", responsabilidad: \"R\", contrato: \"CC\", cveMoneda: \"MX\", numFirma: 12345, mtoContrato: 6000}");
				//renglon 17: MILTON	MIRANDA	MANZANO	23/03/1981	MIMM810323	M80	PERU 110	ACAYUCAN	(null)	POZA RICA DE HIDALGO	(null)	VER 	933	20		9282	24/09/2004	1/01/1900	R	CC	3000
				xConsulta =  INTFConsulta.generaINTF("{nombre: \"MILTON\", aPaterno: \"MIRANDA\", aMaterno: \"MANZANO\", fecNacimiento: \"23031981\", rfc: \"MIMM810323M80\", domicilio: \"PERU 110 ACAYUCAN\", colonia: \"\", municipio: \"POZA RICA DE HIDALGO\", ciudad: \"\", estado: \"VER\", cp:\"93320\", tarjetaTiene: \"V\", tarjetaNum: \"9282\", hipotecarioTiene: \"F\", automotrizTiene: \"F\",  referencia: \"GRALCERTIF66\", responsabilidad: \"R\", contrato: \"CC\", cveMoneda: \"MX\", numFirma: 12345, mtoContrato: 3000}");
				//renglon 19: MILTON	MIRANDA	MANZANO	23/03/1981	MIMM810323	M80	PERU 10	VER	(null)	POZA RICA DE HIDALGO	(null)	VER 	933	20		9282	24/09/2004	1/01/1900	R	CC	3000
				xConsulta =  INTFConsulta.generaINTF("{nombre: \"MILTON\", aPaterno: \"MIRANDA\", aMaterno: \"MANZANO\", fecNacimiento: \"23031981\", rfc: \"MIMM810323M80\", domicilio: \"PERU 10 VER\", colonia: \"27 DE SEP\", municipio: \"POZA RICA DE HIDALGO\", ciudad: \"\", estado: \"VER\", cp:\"93320\", tarjetaTiene: \"V\", tarjetaNum: \"6152\", hipotecarioTiene: \"F\", automotrizTiene: \"F\",  referencia: \"GRALCERTIF66\", responsabilidad: \"R\", contrato: \"CC\", cveMoneda: \"MX\", numFirma: 12345, mtoContrato: 3000}");
				*/
				xConsulta = INTFConsulta.generaINTF(sValores);
				System.out.println("consulta: " + xConsulta);
				if (xConsulta.contains("ERROR")) {
					return -1;
				}
				else {
				    buroSocket= new Socket(host, 35001);
		            buroSocket.setTcpNoDelay(true);
		            buroSocket.setOOBInline(true);
					out = new DataOutputStream( buroSocket.getOutputStream() );
					out.writeBytes(xConsulta);
					out.write( '\u0013' );
					out.write( '\n' );
					out.write( '\n' );
					in = new DataInputStream( buroSocket.getInputStream() );
				    int car = 0;
			        StringBuffer sb = new StringBuffer();
		            try { 
						System.out.println("empieza lectura");
						while ( (car = in.read() ) >= 0 )  {
							//if ( car == '\*u0013')
								//break;
							sb.append((char)car);
						}
					} catch( Exception e ) {
						Logger.getAnonymousLogger().info("dentrodel try de lectura " + e.toString());
						for( int i=0; i!= e.getStackTrace().length; i++) {
							Logger.getAnonymousLogger().info(e.getStackTrace()[i].toString());
						}
					}
		            xrespuestaBuro = sb.toString();
		            System.out.println(" respuesta: " + xrespuestaBuro);
		            out.close();
		            in.close();
		            folio = inserta_tabla_consultacirculo(xConsulta, xrespuestaBuro, Solicitante, noEtapa , valorEtapa, ProductoRequerido, TipoCuenta,
			          ClaveUnidadMonetaria, ImporteContrato, usuario_id_sol, usuario_id_aut, computadora, confirma, sucursal);
		            if (folio > 0 ) {
		            	INTFLectura resp = new INTFLectura();
		            	Integer sFolio = new Integer(folio);
		            	resp.respuesta(conn,sFolio.toString());
		            }
				}
			} catch(Exception se) {
				folio = -1;
				Logger.getAnonymousLogger().info(se.toString());
	            for( int i=0; i!= se.getStackTrace().length; i++) {
		                Logger.getAnonymousLogger().info(se.getStackTrace()[i].toString());
	        	}	    
	            //folio = inserta_tabla_consultacirculo(se.toString(), xrespuestaBuro, Solicitante, noEtapa , valorEtapa, ProductoRequerido, TipoCuenta,
				  //         ClaveUnidadMonetaria, ImporteContrato, usuario_id_sol, usuario_id_aut, computadora, confirma, sucursal);
			}  	
		} catch( Exception se) {
			folio = -1;
			Logger.getAnonymousLogger().info(se.toString());
	        for( int i=0; i!= se.getStackTrace().length; i++) {
	                Logger.getAnonymousLogger().info(se.getStackTrace()[i].toString());
	        }	
	        //folio = inserta_tabla_consultacirculo(se.toString(), xrespuestaBuro, Solicitante, noEtapa , valorEtapa, ProductoRequerido, TipoCuenta,
			  //         ClaveUnidadMonetaria, ImporteContrato, usuario_id_sol, usuario_id_aut, computadora, confirma, sucursal);
		}  finally{
			 try { conn.close(); } catch( Exception e ) {}
		}
		return folio;
	}
	
	Statement st = null;
	
	public  int respuesta( Connection conn, String sfolio) {
		int idr = 0;
		String sINTL = "";
		String sNombre = "";
		String sDireccion = "";
		String sEmpleo = "";
		String sCuentas = "";
		String xEtiq = "";
		int longitud = 0;
		String Error="";
		String Err="";
		String sTexto="";
		String sValorn = "select respuestaxml from consultas_circulo where folioconsulta = '" + sfolio + "'";
		INTFLectura iLectura = new INTFLectura();
		try{
			
	 
			st = conn.createStatement();
			
			String sInsert = "Delete from circulo_personas where folioconsultaotorgante = " + sfolio;
		    st.execute(sInsert);
		    sInsert = "Delete from circulo_domicilios where folioconsultaotorgante = " + sfolio;
		    st.execute(sInsert);
		    sInsert = "Delete from circulo_cuentas where folioconsultaotorgante = " + sfolio;
		    st.execute(sInsert);
		    sInsert = "Delete from circulo_empleos where folioconsultaotorgante = " + sfolio;
		    st.execute(sInsert);
		    sInsert = "Delete from circulo_consultas_efectuadas  where folioconsultaotorgante = " + sfolio;
		    st.execute(sInsert);
			ResultSet res2 = st.executeQuery(sValorn);
			res2.next();
			sTexto = res2.getString(1);	
			
			Logger.getAnonymousLogger().info(sTexto);
			int par = Integer.valueOf(sfolio);
			Logger.getAnonymousLogger().info(String.valueOf(par));
			if(sTexto != "")
			{
				int b1 = 0;
				longitud = sTexto.length();
				Logger.getAnonymousLogger().info(String.valueOf(longitud));					
				if (longitud > 0) {
					sINTL = sTexto.substring(47,48);
					if (longitud < 382) {
						sNombre = sTexto.substring(49,longitud);	//NOMBRE
					}
					else {
						sNombre = sTexto.substring(49,384);	//NOMBRE
					}
					Logger.getAnonymousLogger().info("1");
					b1 = iLectura.lee_nombre(sNombre, par);
					if(b1 < 0)
					{
						Error = "NOMBRE";
						longitud = -1;
					}
					System.out.println("B1: "+String.valueOf(b1)+" Long: "+String.valueOf(longitud));
						b1 += 49;
						if (b1 < longitud) {
							do {
								if (longitud < (b1+313)) {
									sDireccion = sTexto.substring(b1,longitud);
								}
								else {
									System.out.println("Longitud texto: " + String.valueOf(sTexto.length()));
									sDireccion = sTexto.substring(b1,b1+315);
									System.out.println("La cadena dirección es: " + sDireccion);
								}
								b1 += iLectura.lee_dir(sDireccion, par);
								xEtiq = sTexto.substring(b1,b1+2);
							} while (xEtiq.equals("PA") && b1 != -1);
							if(b1 < 0)
							{
								Error += ", DIRECCION";
								idr = longitud = -1;
							}
						}
						if (b1 < longitud) {
							System.out.println("Empleo B1: " + String.valueOf(b1)+ " Long: " + String.valueOf(longitud));
							do {
								if (longitud < (b1+453)) {
									sEmpleo = sTexto.substring(b1,longitud);
								}
								else {
									if((b1+545) <= longitud )
									{
									sEmpleo = sTexto.substring(b1,b1+545);
									}
									else
									{
										sEmpleo = sTexto.substring(b1,longitud);
									}
									System.out.println("La cadena de empleo es: " + sEmpleo);
								}
								b1 += iLectura.lee_empleo(sEmpleo, par);
								xEtiq = sTexto.substring(b1,b1+2);
							} while (xEtiq.equals("PE") && b1 != -1);
							
							if(b1 < 0)
							{
								Error = ", EMPLEO";
								idr = longitud += -1;
							}
							System.out.println("Salio del segmento de empleo, con B1 en: " + b1);
						}
						if (b1 < longitud) {
							System.out.println("Entra al segmento de cuentas, con B1 en: " + b1 +" y Longitud en: " + longitud);	
							do {
								if (longitud < (b1+478)) {
									sCuentas = sTexto.substring(b1,longitud);
									System.out.println("La cadena de cuentas es: " + sCuentas);
								}
								else {
									
									//sCuentas = sTexto.substring(b1,b1+480);
									if 	(longitud < (b1+480))
										sCuentas = sTexto.substring(b1,longitud);										
									else
										sCuentas = sTexto.substring(b1,b1+480);
									System.out.println("La cadena de cuentas es: " + sCuentas);
								}
								b1 += iLectura.lee_cuentas(sCuentas, par);
								xEtiq = sTexto.substring(b1,b1+2);
								
							} while (xEtiq.equals("TL") && b1 != -1);
							if(b1 < 0)
							{
								Error += ", CUENTAS";
								idr = longitud = -1;
							}
							
						}
						if (b1 < longitud) {
						do {
							if (longitud < (b1+133)) {
								sCuentas = sTexto.substring(b1,longitud);
								System.out.println("La cadena de consultas es: " + sCuentas);
							}
							else {
								sCuentas = sTexto.substring(b1,b1+135);
								System.out.println("La cadena de consultas es: " + sCuentas);
							}
							b1 += iLectura.lee_consultas(sCuentas, par);
							xEtiq = sTexto.substring(b1,b1+2);
						} while (xEtiq.equals("IQ") && b1 != -1);
						if(b1 < 0)
						{
							Error = ", CONSULTAS";
							idr = longitud = -1;
						}
					}
				}
				if(idr==-1)
				{
					Err = "ERROR DE INTERPRETACION DE SEGMENTO(S): " + Error;
					try
					{
					conn.rollback();
					}
					catch(Exception z)
					{
						Logger.getAnonymousLogger().info(z.toString());
			                        for( int i=0; i!= z.getStackTrace().length; i++) {
		                        	        Logger.getAnonymousLogger().info(z.getStackTrace()[i].toString());
                			        }
			       }					
				}
				
			}
		} catch(Exception e) {
			e.printStackTrace();
			idr=-1;
			Err+=e.toString();
			try
			{
			conn.rollback();
			}
			catch(Exception z)
			{
			}
		}	
		if(idr == -1)
		{		
			String sInsert = "Update consultas_circulo set error = '" + Err + "' , fecha_creacion = CURRENT_TIMESTAMP where folioconsulta = " + sfolio;
		    try{
			    st.execute(sInsert);
			    Logger.getAnonymousLogger().info(sInsert);
			} catch(Exception e) {
			        Logger.getAnonymousLogger().info(e.toString());
                	for( int i=0; i!= e.getStackTrace().length; i++) {
        	               Logger.getAnonymousLogger().info(e.getStackTrace()[i].toString());
	                }
			}    				
		}
		else
		{
			Logger.getAnonymousLogger().info(sValorn);
			String numc = iLectura.lee_fin(sTexto);
			String sInsert2 = "Update consultas_circulo set control = '" + numc + "' , cuenta_buro = "+ numc + 
			  " , fecha_creacion = CURRENT_TIMESTAMP where folioconsulta = " + sfolio ; 
			try{
				System.out.println("control , " + sInsert2);
				st.execute(sInsert2);
				Logger.getAnonymousLogger().info(sInsert2);
			}
			catch(Exception e){
				Logger.getAnonymousLogger().info(e.toString());
                for( int i=0; i!= e.getStackTrace().length; i++) {
                        Logger.getAnonymousLogger().info(e.getStackTrace()[i].toString());
    	        }
			}
			
			sInsert2 = "select buro_califica_cuentas(" + sfolio +")"; 
			try{
				System.out.println("control , " + sInsert2);
				st.execute(sInsert2);
				Logger.getAnonymousLogger().info(sInsert2);
			}
			catch(Exception e){
				Logger.getAnonymousLogger().info(e.toString());
              for( int i=0; i!= e.getStackTrace().length; i++) {
                      Logger.getAnonymousLogger().info(e.getStackTrace()[i].toString());
  	        }
              
			}
		}
		try { conn.close(); } catch( Exception ex ) {}			
		return idr     ;
	}
			 
	int iFolioid ;
			
	private  int inserta_tabla_consultacirculo(String con, String res, String idsol, int noEtapa ,String valorEtapa, int ProductoRequerido, String TipoCuenta, String ClaveUnidadMonetaria,double ImporteContrato, int usuario_id_sol,int usuario_id_aut, String computadora,int confirma, String sucursal)
	{	
		
		try { 
			
			String sValorn = "select nextval('Consultas_circulo_s')";
			try {			
				ResultSet res2 = st.executeQuery(sValorn);
				res2.next();
				iFolioid = res2.getInt(1);		
			}catch(Exception e) {
				Logger.getAnonymousLogger().info(e.toString());
	            for( int i=0; i!= e.getStackTrace().length; i++) {
	                    Logger.getAnonymousLogger().info(e.getStackTrace()[i].toString());
		        }
				iFolioid = -1;
			}	
			//6546666666666666666666666666666666666666666666666666666666666666546546456456
			if(iFolioid != -1)
			{
				java.util.regex.Pattern p = java.util.regex.Pattern.compile("'");
				java.util.regex.Matcher m = p.matcher(res);
				StringBuffer sb = new StringBuffer();
				while (m.find()) {
				     m.appendReplacement(sb, " ");
				}
				m.appendTail(sb);
				res= sb.toString();
				 
				String sInsert2 = "Insert into consultas_circulo(folioconsulta, buro, respuestaxml, consultaxml, solicitante, noetapa, valoretapa, tipocuenta, claveunidadmonetaria, importecontrato, usuario_id_sol, usuario_id_aut, computadora, confirma, sucursal, productorequerido, fecha_creacion) values("
					+ iFolioid + ",'B','" + res +"','" + con + "','" + idsol + "'," + String.valueOf(noEtapa) + ",'" + valorEtapa + "','" + TipoCuenta + "','" + ClaveUnidadMonetaria + "',"+ String.valueOf(ImporteContrato) + ","+ String.valueOf(usuario_id_sol) + "," + String.valueOf(usuario_id_aut) + ",'" + computadora + "'," + String.valueOf(confirma) + ",'" + sucursal + "'," + String.valueOf(ProductoRequerido) + ", CURRENT_TIMESTAMP)";
				System.out.println(sInsert2);
				st.execute(sInsert2);
				Logger.getAnonymousLogger().info(sInsert2);
				
			}	
		}
		catch(Exception e){
			Logger.getAnonymousLogger().info(e.toString());
	        for( int i=0; i!= e.getStackTrace().length; i++) {
	                Logger.getAnonymousLogger().info(e.getStackTrace()[i].toString());
	        }
		}	
		return iFolioid;
	}
		}

