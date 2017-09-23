package net.sistacc.ws.buro;

import java.io.DataInputStream;
import java.io.DataOutputStream;
import java.net.Socket;
import java.sql.Connection;
import java.sql.Date;
import java.sql.ResultSet;
import java.sql.Statement;
import  java.util.logging.Logger;

public class BuroPFImpl  {
	Statement st = null;
	int iFolioid ;
	
	public int consulta(Connection conn, String Solicitante,int noEtapa ,String valorEtapa, int ProductoRequerido, String TipoCuenta,
            String ClaveUnidadMonetaria,double ImporteContrato, int usuario_id_sol,int usuario_id_aut, 
            String computadora,int confirma, String sucursal) {
		int folio = -1;
		String xConsulta = "";
		DataOutputStream out = null;
	    DataInputStream in = null;		
       // char[] term =  new char[] { 0x00, '\u0013' };
		Logger.getAnonymousLogger().info(Solicitante);
		Socket buroSocket;
		String xrespuestaBuro  = "";
		
		try {
			try{
				  
				 st = conn.createStatement();
				 
				 String sqldatos = "select usuario_buro,contrasena_buro,servidor_buro,puerto_buro from cat_empresas where clave = " + sucursal;				 
				 ResultSet res = st.executeQuery(sqldatos);
				 res.next();
				 String usuario = res.getString("usuario_buro");
				 String contrasena = res.getString("contrasena_buro");
		 		 String host = res.getString("servidor_buro");
				 String puerto = res.getString("puerto_buro");
				 int puerto_buro_credito = Integer.parseInt(puerto);

				 xConsulta = cadenaconsulta(Solicitante, (int)ImporteContrato, ProductoRequerido, usuario, contrasena);
				//Quitamos la letra � de la cadena de consulta para que no marque error en buro.
				 xConsulta = xConsulta.replace("�", "N");
				 
			     buroSocket= new Socket(host, puerto_buro_credito);
	             buroSocket.setTcpNoDelay(true);
	             buroSocket.setOOBInline(true);
			//	 String xSegmentoAU = "AU03RCN000130125                         0201F040400000701F1101F";
				// Logger.getAnonymousLogger().info(xConsulta + new String( term )    );			
			//	 String sFin = fin( xSegmentoAU + xConsulta );
			//	 if (!sFin.equals("")) {
			//		 xConsulta = xSegmentoAU + xConsulta  + sFin;
				 //}
				 Logger.getAnonymousLogger().info("lectura:" + xConsulta );
				 System.out.println(xConsulta);
			     // out = new OutputStreamWriter( buroSocket.getOutputStream() , "ISO-8859-1" ); 
                     out = new DataOutputStream( buroSocket.getOutputStream() );
				 //OutputStream o = buroSocket.getOutputStream();
				 //xConsulta = "INTL11REFERENCIA OPERADOR      005MX0000ZM27341002lZj89(T_ITE           SP03A    000000APN05CAUSA0007PERDIDA0205TOTAL0305MENTE0510CAMT7004040602SR0802MXPA20NINO ENCONTRADO 54210106CENTRO0308TUXTEPEC0403OAX050568300ES05002260002**";
				 //xConsulta = "INTL11                         001MX0000ZM27341002P7BsHZpDILCMX000050000EN01S    0000000PN07SALAZAR0008GAVARAIN0210XOCHIPILLI0408220919780510SAGX7809220802MX1101M1201FPA06ZAFIRO0110LAS PALMAS0206LA PAZ0306LA PAZ0403BCS050523083ES05002260002**";
	             //xConsulta = "INTL11REFERENCIA OPERADOR      005MX0000MEMBERCODEPASSWD08ITE           SP03A    000000APN05CAUSA0007PERDIDA0205TOTAL0305MENTE0510CAMT7004040602SR0802MXPA20NINO ENCONTRADO 54210106CENTRO0308TUXTEPEC0403OAX050568300ES05002260002**";
				 Logger.getAnonymousLogger().info(xConsulta);
				 out.writeBytes(xConsulta);
				 out.write( '\u0013' );
				 out.write( '\n' );
				 out.write( '\n' );
		               in = new DataInputStream( buroSocket.getInputStream() );
		         //String linea = "";
		         
			     int car = 0;
		         StringBuffer sb = new StringBuffer();
	             try { 
	                Logger.getAnonymousLogger().info("empieza lectura");
					while ( (car = in.read() ) >= 0 )  {
						if ( car == '\u0013') 
							 break;
	                    
						sb.append((char)car);
					}
	            } catch( Exception e ) {
	                Logger.getAnonymousLogger().info(e.toString());
	                for( int i=0; i!= e.getStackTrace().length; i++) {
	                        Logger.getAnonymousLogger().info(e.getStackTrace()[i].toString());
	                }
	
			    }
				xrespuestaBuro = sb.toString(); 
				//xrespuestaBuro += new String( buf ).substring(0,car); 
				Logger.getAnonymousLogger().info("regreso "  +xrespuestaBuro);
				//xrespuestaBuro =  linea;
				out.close();
				in.close();
				//linea agregada 13102010
				folio = inserta_tabla_consultacirculo(xConsulta, xrespuestaBuro, Solicitante, noEtapa , valorEtapa, ProductoRequerido, TipoCuenta,
				          ClaveUnidadMonetaria, ImporteContrato, usuario_id_sol, usuario_id_aut, computadora, confirma, sucursal);
			} catch(Exception se) {
				folio = -1;
				Logger.getAnonymousLogger().info(se.toString());
	            for( int i=0; i!= se.getStackTrace().length; i++) {
		                Logger.getAnonymousLogger().info(se.getStackTrace()[i].toString());
	        	}	    
	            folio = inserta_tabla_consultacirculo(se.toString(), xrespuestaBuro, Solicitante, noEtapa , valorEtapa, ProductoRequerido, TipoCuenta,
				           ClaveUnidadMonetaria, ImporteContrato, usuario_id_sol, usuario_id_aut, computadora, confirma, sucursal);
			}  	
			/*modificacion de linea 13102010
			 * folio = inserta_tabla_consultacirculo(xConsulta, xrespuestaBuro, Solicitante, noEtapa , valorEtapa, ProductoRequerido, TipoCuenta,
		          ClaveUnidadMonetaria, ImporteContrato, usuario_id_sol, usuario_id_aut, computadora, confirma, sucursal);*/
		} catch( Exception se) {
			folio = -1;
			Logger.getAnonymousLogger().info(se.toString());
	        for( int i=0; i!= se.getStackTrace().length; i++) {
	                Logger.getAnonymousLogger().info(se.getStackTrace()[i].toString());
	    	}	
	        folio = inserta_tabla_consultacirculo(se.toString(), xrespuestaBuro, Solicitante, noEtapa , valorEtapa, ProductoRequerido, TipoCuenta,
			           ClaveUnidadMonetaria, ImporteContrato, usuario_id_sol, usuario_id_aut, computadora, confirma, sucursal);
		}  finally{
			 try { conn.close(); } catch( Exception e ) {}
		}
		return folio;
	}
		
	public  int respuesta(Connection conn, String sfolio) {
		// TODO Auto-generated method stub
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
					b1 = lee_nombre(sNombre, par);
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
									System.out.println("La cadena direcci�n es: " + sDireccion);
								}
								b1 += lee_dir(sDireccion, par);
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
								b1 += lee_empleo(sEmpleo, par);
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
								b1 += lee_cuentas(sCuentas, par);
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
							b1 += lee_consultas(sCuentas, par);
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
			Logger.getAnonymousLogger().info(e.toString());
                        for( int i=0; i!= e.getStackTrace().length; i++) {
                                Logger.getAnonymousLogger().info(e.getStackTrace()[i].toString());
                        }

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
			String numc = lee_fin(sTexto);
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
		
	private  String cadenaconsulta(String nSolicitante, int nMonto, int ProductoRequerido, String usuario, String contrasena) {
		String sINTL="";
		String sNombre = "";
		String sDom = "";
		String sConsulta ="";
		//sINTL = encabezado("MC27341032","dLtvHesq",nMonto);
		//sINTL = encabezado("MC27341032","8PoF2AVq",nMonto);
		//sINTL = encabezado("MC27341032","cPoF2ArX",nMonto);
		//sINTL = encabezado("MC27341032","cPoF2ArI",nMonto);
		//sINTL = encabezado("MC27341032","cPoF2Ar3",nMonto);
		//sINTL = encabezado("MC27341032","cPoF2GAW",nMonto);
		//sINTL = encabezado("MC27341032","cPoF2KRW",nMonto);
		//sINTL = encabezado("MC27341032","uBrF2KRW",nMonto);
		//sINTL = encabezado("MC27341032","uBrF2LOE",nMonto);
		//sINTL = encabezado("MC27341032","ugdZZ1WQ",nMonto);
		//sINTL = encabezado("MC27341032","ugdZZeWQ",nMonto);
		//sINTL = encabezado("MC27341032","ugdZZerl",nMonto);
		//sINTL = encabezado("MC27341032","u7Bss3BT",nMonto);
		//sINTL = encabezado("MC27341032","u7Bss3By",nMonto);
		//sINTL = encabezado("MC27341032","nlFaJe4x",nMonto);
		//sINTL = encabezado("MC27341032","udFaJe3Z",nMonto);
		//sINTL = encabezado("MC27341032","uWFaJeky",nMonto); bloqueo dia 19 de nov 2009
		//sINTL = encabezado("MC27341032","YnFaJe4x",nMonto);
		//sINTL = encabezado("MC27341032","nlsPimPT",nMonto);
		//sINTL = encabezado("MC27341032","ECssi63Z",nMonto);
		//sINTL = encabezado("MC27341032","udsoi6ks",nMonto);
		//sINTL = encabezado("MC27341032","nlFaJe4x",nMonto);
		//sINTL = encabezado("MC27341032","udsoi63Z",nMonto);
		//sINTL = encabezado("MC27341032","ECFaJeks",nMonto);
		//sINTL = encabezado("MC27341032","uxsoi6PT",nMonto);
		//sINTL = encabezado("MC27341032","wCsoi6PR",nMonto);
		//sINTL = encabezado("MC27341032","urFaJe4x",nMonto);
		//sINTL = encabezado("MC27341032","wnFaJePx",nMonto);
		//sINTL = encabezado("MC27341032","nlFaJePx",nMonto);
		//sINTL = encabezado("MC27341032","nlsoi6Px",nMonto);
		//sINTL = encabezado("MC27341032","wWsoi6Px",nMonto);
		//sINTL = encabezado("MC27341032","udssi63Z",nMonto);
		//sINTL = encabezado("MC27341032","nlssi64x",nMonto);
		//sINTL = encabezado("MC27341032","udFaJI3Z",nMonto);
		//sINTL = encabezado("MC27341032","ECFaJI3Z",nMonto);
		//sINTL = encabezado("MC27341032","uxFaJIgx",nMonto);
		//sINTL = encabezado("MC27341032","udFaJIkT",nMonto);
		//sINTL = encabezado("MC27341032","udFaJIYA",nMonto);
		//sINTL = encabezado("MC27341032","udssJIkT",nMonto);
		//sINTL = encabezado("MC27341032","uAsLiNYO",nMonto);
		//sINTL = encabezado("MC27341032","uAsoi6YO",nMonto);
		//sINTL = encabezado("MC27341032","udsLiN3Z",nMonto);
		//sINTL = encabezado("MC27341032","tHsPiPks",nMonto);
		sINTL = encabezado(usuario,contrasena,nMonto, ProductoRequerido);
		if (!sINTL.equals("")) {			
			sNombre = nombre(nSolicitante);
			if (!sNombre.equals("NOMBRE")) {
				sDom = domicilio(nSolicitante);
				if (!sDom.equals("DOMIC")) {
					//sFin = fin(sINTL + sNombre + sDom);
					//if (!sFin.equals("")) {
					//	sConsulta = sINTL +sNombre + sDom + sFin;
					//}
					sConsulta = sINTL +sNombre + sDom; 
				}
			}
		}
	    int lgt = 15;
	    lgt += sConsulta.length();
	    String Es = "";
	    if(lgt >=0 && lgt < 10)
	    {
	    	Es = "ES050000" + String.valueOf(lgt) + "0002**";
	    }
	    if(lgt >=10 && lgt < 100)
	    {
	    	Es = "ES05000" + String.valueOf(lgt) + "0002**";
	    }
	    if(lgt >=100 && lgt < 1000)
	    {
	    	Es = "ES0500" + String.valueOf(lgt) + "0002**";
	    }
	    if(lgt >=1000 && lgt < 10000)
	    {
	    	Es = "ES050" + String.valueOf(lgt) + "0002**";
	    }
	    if(lgt >=10000 && lgt < 100000)
	    {
	    	Es = "ES05" + String.valueOf(lgt) + "0002**";
	    }
	   
		return sConsulta + Es;	
	}
	
	private  String encabezado(String clave,String pass, int mont, int ProductoRequerido) {
		boolean b1;
		
		String sEnc="";
		
			String sSql = "select coalesce(lim_sup,0) as lim_sup from cat_creditos where clave=" + ProductoRequerido;
			int monto=0; 
			int monto2 = mont;
			String sMonto = "000000000";
			try {
				ResultSet res = st.executeQuery(sSql);
				while (res.next()) {
					monto = res.getInt("lim_sup");
				}
				if (monto>99) {
					sEnc="INTL";		//Etiqueta
					sEnc=sEnc + "11"; 	//Version
					sEnc=sEnc + "                         "; 	//Referencia 25 espacios
					sEnc=sEnc + "001"; 	//Producto
					sEnc=sEnc + "MX"; 	//Pais
					sEnc=sEnc + "0000";	//Identificador
					sEnc=sEnc + clave; 	//Clave
					sEnc=sEnc + pass; 	//Password
					sEnc=sEnc + "I"; 	//Indicador
					sEnc=sEnc + "CL"; 	//Contrato valo original LC cambio a CL
					sEnc=sEnc + "MX"; 	//Pesos
					/*sMonto = sMonto + String.valueOf(monto2);//;monto;
					monto = sMonto.length();*/
					String mto=String.valueOf(monto2);
					
					sMonto = sMonto.substring(0,sMonto.length() - mto.length());
					sMonto = sMonto + mto;
					
					sEnc=sEnc + sMonto; 	//Monto
					sEnc=sEnc + "SP"; 	//Idioma
					sEnc=sEnc + "01"; 	//Tipo Salida
					sEnc=sEnc + "S    "; 	//Tamañ				sEnc=sEnc + "    "; 	//Impresora
					sEnc=sEnc + "0000000"; 	//Pesos
				}
			} catch(Exception e) { 
				Logger.getAnonymousLogger().info(e.toString());
                	        for( int i=0; i!= e.getStackTrace().length; i++) {
        	                        Logger.getAnonymousLogger().info(e.getStackTrace()[i].toString());
	                        }

			}			
			System.out.println(sEnc);
		return sEnc;
	}
	
	private  String nombre(String idSolicitante) {
		String sSol="NOMBRE";
		/*String fFecAct = "";
		String sSql = "select current_Date as vescribe";
		try {
			ResultSet res = st.executeQuery(sSql);
			fFecAct = res.getString("vescribe");			
		} catch(Exception e) {
			Logger.getAnonymousLogger().info(e.toString());
                        for( int i=0; i!= e.getStackTrace().length; i++) {
                                Logger.getAnonymousLogger().info(e.getStackTrace()[i].toString());
                        }
         }
			
		//String sSql = "select buro_nombre('A','" + idSolicitante + "',1,1,'') as vescribe";*/
		String sSql = "select buro_nombre('A','" + idSolicitante + "',1,1,'', current_date) as vescribe";
		Logger.getAnonymousLogger().info("Cadena de nombre:" + sSql );
		try {
			ResultSet res = st.executeQuery(sSql);
			while (res.next()) {
				sSol = res.getString("vescribe");
			}
		} catch(Exception e) {
			Logger.getAnonymousLogger().info(e.toString());
                        for( int i=0; i!= e.getStackTrace().length; i++) {
                                Logger.getAnonymousLogger().info(e.getStackTrace()[i].toString());
                        }

		}
		return sSol;
	}
	
	private  String domicilio(String idSolicitante) {
		String sSol="DOMIC";
		String sSql = "select buro_domicilio('A','" + idSolicitante + "',1,1) as vescribe";
		//sSql = "select buro_domicilio('A','" + idSolicitante + "',1,1,'','" + fFecAct +"') as vescribe";
		try {
			ResultSet res = st.executeQuery(sSql);
			while (res.next()) {
				sSol = res.getString("vescribe");
			}
			Logger.getAnonymousLogger().info(sSol);
		} catch(Exception e) {
			Logger.getAnonymousLogger().info(e.toString());
                        for( int i=0; i!= e.getStackTrace().length; i++) {
                                Logger.getAnonymousLogger().info(e.getStackTrace()[i].toString());
                        }
		}			
		return sSol;
	}
	
	private  String fin(String sResultado) {
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
		
	private  int lee_nombre(String pNombre, int fol) {
		String sPropio = "";
		String sSegundo = "";
		String sAdicional = "";
		String sPaterno = "";
		String sMaterno = "";
		String sFecha = "";
		String sRFC = "";
		String sPrefijo = "";
		String sSufijo = "";
		String sNac = "";
		String sRef = "";
		String sLic = "";
		String sEdoCivil = "";
		String sSexo = "";
		String sCedula  = "";
		String sElectoral = "";
		String sImpuesto = "";
		String sPais = "";
		String sDep = "0";
		String sEdades = "";
		String sInforma = "";
		String sDef = "";
		String sEtiq = "";
		String sLong = "";
		Integer iPos = 0;
		Integer iLong = 0;
		Date fFecNac = new Date(0,9,9); //= new Date();
		int xLong = pNombre.length();		
		while (iPos < xLong) {
			sEtiq = pNombre.substring(iPos,iPos+2);
			iPos = iPos + 4;
			sLong = pNombre.substring(iPos-2,iPos);
			iLong = iLong.parseInt(sLong);
			if (sEtiq.equals("PN")) {
				sPaterno = pNombre.substring(iPos,iPos+iLong);  //Apellido Paterno
			}
			if (sEtiq.equals("00")) {
				sMaterno = pNombre.substring(iPos,iPos+iLong);  //Apellido Materno
			}
			if (sEtiq.equals("01")) {
				sAdicional = pNombre.substring(iPos,iPos+iLong);  //Apellido Adicional
			}
			if (sEtiq.equals("02")) {
				sPropio = pNombre.substring(iPos,iPos+iLong);  //Nombre Propio
			}
			if (sEtiq.equals("03")) {
				sSegundo = pNombre.substring(iPos,iPos+iLong);  //Segund Nombre
			}
			if (sEtiq.equals("04")) {				
				sFecha = pNombre.substring(iPos,iPos+iLong); 	  //Fec Nacimiento				
				fFecNac = convierte_fecha(sFecha);
			}
			if (sEtiq.equals("05")) {				
				sRFC = pNombre.substring(iPos,iPos+iLong); 	  //RFC
			}
			if (sEtiq.equals("08")) {				
				sNac = pNombre.substring(iPos,iPos+iLong); 	  //Nacionalidad
			}
			if (sEtiq.equals("11")) {				
				sEdoCivil = pNombre.substring(iPos,iPos+iLong); 	  //Estado Civil
			}
			
			if (sEtiq.equals("12")) {
				sSexo = pNombre.substring(iPos,iPos+iLong); 	  //Sexo
			}
			if (sEtiq.equals("14")) {				
				sElectoral = pNombre.substring(iPos,iPos+iLong); 	  //IFE
			}
			if (sEtiq.equals("17")) {
				sDep = pNombre.substring(iPos,iPos+iLong); 	  //Dependientes									
			}
			if ((sEtiq.equals("PA")) || (sEtiq.equals("PE")) || (sEtiq.equals("TL")) || (sEtiq.equals("IQ"))  || (sEtiq.equals("RS")))  {
				iLong = iPos - 4;
				iPos = xLong;
			}
			iPos += iLong;
		}		
		String sInsert = "Insert into circulo_personas(folioconsultaotorgante, nombres, apellidopaterno, apellidomaterno, apellidoadicional, " +
				"fechanacimiento, rfc, nacionalidad, estadocivil, numerodependientes, sexo, claveife) values(" +
		fol + ",'" + sPropio + " " + sSegundo + "','" +  sPaterno + "','" + sMaterno + "','" + sAdicional + "','" + fFecNac + "','" + sRFC + "','" +
		sNac + "','" + sEdoCivil + "'," + sDep + ",'" + sSexo +"','" + sElectoral +"')";
		try{
			Logger.getAnonymousLogger().info(sInsert);
			st.execute(sInsert);			
		} 
		catch(Exception e)
		{
			Logger.getAnonymousLogger().info(e.toString());
                        for( int i=0; i!= e.getStackTrace().length; i++) {
                                Logger.getAnonymousLogger().info(e.getStackTrace()[i].toString());
                        }
			iLong = -1;
		} 
		return iLong;
	}	

	private  int lee_dir(String pDir, int fol) {
		String sDir1 = "";
		String sDir2 = "";
		String sCol = "";
		String sMun = "";
		String sCid = "";
		String sEdo = "";
		String sCP = "0";
		String sFecRes = "";
		String sTel = "";
//		String sFax = "";
//		String sTipDom = "";
//		String sIndDom = "";
		String sFecDom = "";
		String sEtiq = "";
		String sLong = "";
		Date fFecRes = new Date(0,9,9);
		Date fFecDom = new Date(0,9,9);
		Integer iPos = 0;
		Integer iLong = 0;
		int xLong = pDir.length();
		while (iPos < xLong) {
			sEtiq = pDir.substring(iPos,iPos+2);
			iPos = iPos + 4;
			sLong = pDir.substring(iPos-2,iPos);
			iLong = iLong.parseInt(sLong);
			if (sEtiq.equals("PA")) {
				if (iPos == 4) {
					sDir1 = pDir.substring(iPos,iPos+iLong);  //Domicilio 1
					sEtiq = "";
				}
			}
			if (sEtiq.equals("00")) {
				sDir2 = pDir.substring(iPos,iPos+iLong);  //Domicilio 2
			}
			if (sEtiq.equals("01")) {
				sCol = pDir.substring(iPos,iPos+iLong);  //Colonia 
			}
			if (sEtiq.equals("02")) {
				sMun = pDir.substring(iPos,iPos+iLong);  //Municipio
			}
			if (sEtiq.equals("03")) {
				sCid = pDir.substring(iPos,iPos+iLong);  //Ciudad
			}
			if (sEtiq.equals("04")) {
				sEdo = pDir.substring(iPos,iPos+iLong);  //Estado
			}
			if (sEtiq.equals("05")) {
				sCP = pDir.substring(iPos,iPos+iLong);  //Codigo postal
			}
			if (sEtiq.equals("06")) {				
				sFecRes = pDir.substring(iPos,iPos+iLong); 	  //Fecha Residencia
				fFecRes = convierte_fecha(sFecRes);
			}
			if (sEtiq.equals("07")) {				
				sTel = pDir.substring(iPos,iPos+iLong); 	  //Telefono
			}
			if (sEtiq.equals("12")) {				
				sFecDom = pDir.substring(iPos,iPos+iLong); 	  //Fecha Residencia
				fFecDom = convierte_fecha(sFecDom);
			}
			if ((sEtiq.equals("PA")) || (sEtiq.equals("PE")) || (sEtiq.equals("TL")) || (sEtiq.equals("IQ"))  || (sEtiq.equals("RS")))  {
				iLong = iPos - 4;
				iPos = xLong;
			}
			iPos += iLong;
		}
	
		String sInsert = "Insert into circulo_domicilios(folioconsultaotorgante, direccion, ciudad, estado, cp, fecharesidencia, telefono, delegacionmunicipio, coloniapoblacion, fecharegistrodomicilio) values(" +
		fol + ",'" + (sDir1 + " " + sDir2) + "','" + sCid + "','" + sEdo + "'," + sCP + ",'" + fFecRes + "','" + sTel + "','" + sMun + "','" + sCol + "','" +fFecDom + "')";
	    
		try{
		Logger.getAnonymousLogger().info(sInsert);
		st.execute(sInsert);
		} catch(Exception e) {
			Logger.getAnonymousLogger().info(e.toString());
			for( int i=0; i!= e.getStackTrace().length; i++) {
				Logger.getAnonymousLogger().info(e.getStackTrace()[i].toString());
				}
			iLong = -1;
		} 				
		return iLong;
	}	
	
	private int lee_empleo(String pEmp, int fol) {
		String sEmpleo = "";
		String sPuesto = "";
		String sDir1 = "";
		String sDir2 = "";
		String sCol = "";
		String sMun = "";
		String sCid = "";
		String sEdo = "";
		String sCP = "0";
		String sTel = "";
		String sSalario ="0";
		String sFecCon = "01012000";
		String sFecEmp = "01012000";
		String sEtiq = "";
		String sLong = "";
		Date fFecCon = new Date(0,9,9);
		Date fFecEmp = new Date(0,9,9);
		Integer iPos = 0;
		Integer iLong = 0;
		int xLong = pEmp.length();
		
		sEtiq = pEmp.substring(iPos,iPos+2);
		if (sEtiq.equals("PE")) {
		while (iPos < xLong) {
			sEtiq = pEmp.substring(iPos,iPos+2);
			iPos = iPos + 4;
			sLong = pEmp.substring(iPos-2,iPos);
			iLong = iLong.parseInt(sLong);
			if (sEtiq.equals("PE")) {
				if (iPos == 4) {
					sEmpleo = pEmp.substring(iPos,iPos+iLong);  //Nombre Empleo
					sEtiq = "";
				}
			}
			if (sEtiq.equals("00")) {
				sDir1 = pEmp.substring(iPos,iPos+iLong);  //Domicilio 1
			}
			if (sEtiq.equals("01")) {
				sDir2 = pEmp.substring(iPos,iPos+iLong);  //Domicilio 2
			}
			if (sEtiq.equals("02")) {
				sCol = pEmp.substring(iPos,iPos+iLong);  //Colonia 
			}
			if (sEtiq.equals("03")) {
				sMun = pEmp.substring(iPos,iPos+iLong);  //Municipio
			}
			if (sEtiq.equals("04")) {
				sCid = pEmp.substring(iPos,iPos+iLong);  //Ciudad
			}
			if (sEtiq.equals("05")) {
				sEdo = pEmp.substring(iPos,iPos+iLong);  //Estado
			}
			if (sEtiq.equals("06")) {
				sCP = pEmp.substring(iPos,iPos+iLong);  //Codigo postal
			}
			if (sEtiq.equals("07")) {				
				sTel = pEmp.substring(iPos,iPos+iLong); 	  //Telefono
			}
			if (sEtiq.equals("10")) {				
				sPuesto = pEmp.substring(iPos,iPos+iLong); 	  //Puesto
			}
			if (sEtiq.equals("11")) {	
				sFecCon = pEmp.substring(iPos,iPos+iLong);	//Fecha Contracion
				fFecCon = convierte_fecha(sFecCon);
			}
			if (sEtiq.equals("13")) {				
				sSalario = pEmp.substring(iPos,iPos+iLong); 	  //Salario
			}
			if (sEtiq.equals("16")) {	
				sFecEmp = pEmp.substring(iPos,iPos+iLong);	//Fecha Ultimo dia
				fFecEmp = convierte_fecha(sFecEmp);
			}
			if ((sEtiq.equals("PE")) || (sEtiq.equals("TL")) || (sEtiq.equals("IQ"))  || (sEtiq.equals("RS")))  {
				iLong = iPos - 4;
				iPos = xLong;
			}
			iPos += iLong;
		}	
		String sInsert = "Insert into circulo_empleos(folioconsultaotorgante, direccion, coloniapoblacion, delegacionmunicipio, ciudad, estado, telefono, cp, nombreempresa, puesto, salariomensual, fechacontratacion, fechaultimadiaempleo) values(" +
		fol + ",'" + (sDir1 + " " + sDir2) + "','" + sCol + "','" + sMun + "','" + sCid + "','" + sEdo + "','" + sTel + "'," + sCP + ",'" + sEmpleo + "','" + sPuesto + "'," + sSalario + ",'" + fFecCon + "','" + fFecEmp +  "')";
		try{
			Logger.getAnonymousLogger().info(sInsert);
			st.execute(sInsert);
		} catch(Exception e) {
			Logger.getAnonymousLogger().info(e.toString());
            for( int i=0; i!= e.getStackTrace().length; i++) {
                    Logger.getAnonymousLogger().info(e.getStackTrace()[i].toString());
	        }
			iLong = -1;
		}
		}
		else
		{
			fFecCon = convierte_fecha(sFecCon);	
			fFecEmp = convierte_fecha(sFecEmp);
			
			String sInsert = "Insert into circulo_empleos(folioconsultaotorgante, direccion, coloniapoblacion, delegacionmunicipio, ciudad, estado, telefono, cp, nombreempresa, puesto, salariomensual, fechacontratacion, fechaultimadiaempleo) values(" +
			fol + ",'" + (sDir1 + " " + sDir2) + "','" + sCol + "','" + sMun + "','" + sCid + "','" + sEdo + "','" + sTel + "'," + sCP + ",'" + sEmpleo + "','" + sPuesto + "'," + sSalario + ",'" + fFecCon + "','" + fFecEmp +  "')";
			try{
				Logger.getAnonymousLogger().info(sInsert);
				st.execute(sInsert);
			} catch(Exception e) {
				Logger.getAnonymousLogger().info(e.toString());
	            for( int i=0; i!= e.getStackTrace().length; i++) {
	                    Logger.getAnonymousLogger().info(e.getStackTrace()[i].toString());
		        }
				iLong = -1;
			}
			iLong = 0;
		}
		return iLong;
	}	

	private int lee_cuentas(String pCuenta, int fol) {
		String sFecAct = "";
		String sTContrato = "";
		String sImp = "";
		String clOtor = "";
		String nomOtor = "";
		String sLimite = "";
		String sCuenta = "";
		String sResp = "";
		String sTipo = "";
		String sContrato = "";
		String sMoneda = "";
		String sValuacion = "";
		String sNumPagos = "0";
		String sFrecPagos = "";
		String sFecPeor = "";
		String sFecAper = "";
		String sFecUlt = "";
		String sFecUltc = "";
		String sFecComp = "";
		String sFecCierre = "";
		String sFecRep = "";
		String sFecRec = "";
		String sFecAnt = "";
		String sSaldo = "0";
		String sCremax = "0"; //Credito maximo
		String sSaldov = "0";
		String sMonto = "0";
		String telOtor = "";
		String sVencido = "0";
		String sNumVenc = "0";
		String sMOP = "";
		String sObs = "";
		String sMOP2 = "";		
		String sEtiq = "";
		String sLong = "";
		String sHistorial ="";
		Date fFecAct = new Date(0,9,9);
		Date fFecAper = new Date(0,9,9);
		Date fFecUlt = new Date(0,9,9);
		Date fFecUltc = new Date(0,9,9);
		Date fFecComp = new Date(0,9,9);
		Date fFecCierre = new Date(0,9,9);
		Date fFecRep = new Date(0,9,9);
		Date fFecPeor = new Date(0,9,9);
		Date fFecRec = new Date(0,9,9);
		Date fFecAnt = new Date(0,9,9);
		Integer iPos = 0;
		Integer iLong = 0;
		int xLong = pCuenta.length();
		while (iPos < xLong) {
			sEtiq = pCuenta.substring(iPos,iPos+2);
			iPos = iPos + 4;
			sLong = pCuenta.substring(iPos-2,iPos);
			iLong = iLong.parseInt(sLong);
			if (sEtiq.equals("TL")) {
				if (iPos == 4) {
					sFecAct = pCuenta.substring(iPos,iPos+iLong);  //Fecha de Actualizacion
					fFecAct = convierte_fecha(sFecAct);
					sEtiq = "";
				}
			}
			if (sEtiq.equals("00")) {
				sImp = pCuenta.substring(iPos,iPos+iLong);  	//Impugnado por el cliente
			}
			if (sEtiq.equals("01")) {
				clOtor = pCuenta.substring(iPos,iPos+iLong);  	//Clave Otorgante
			}
			if (sEtiq.equals("02")) {
				nomOtor = pCuenta.substring(iPos,iPos+iLong);  //Nombre Otorgante 
			}
			if (sEtiq.equals("03")) {
				telOtor = pCuenta.substring(iPos,iPos+iLong);  //Telefono Otorgante
			}
			if (sEtiq.equals("04")) {
				sCuenta = pCuenta.substring(iPos,iPos+iLong);  	//Cuenta
			}
			if (sEtiq.equals("05")) {
				sResp = pCuenta.substring(iPos,iPos+iLong);  	//Tipo de Responsabilidad
				if(sResp.equals("I"))
					sResp = "INDIVIDUAL";
				if(sResp.equals("J"))
					sResp = "MANCOMUNADO";
				if(sResp.equals("C"))
					sResp = "OBLIGADO SOLIDARIO";								
			}
			if (sEtiq.equals("06")) {
				sTipo = pCuenta.substring(iPos,iPos+iLong);  	//Tipo de Cuenta
				if(sTipo.equals("I"))
					sTipo = "PAGOS FIJOS";
				if(sTipo.equals("M"))
					sTipo = "HIPOTECA";
				if(sTipo.equals("O"))
					sTipo = "SIN LIMITE ESTABLECIDO";
				if(sTipo.equals("R"))
					sTipo = "REVOLVENTE";
				Logger.getAnonymousLogger().info(sTipo + " " + sTipo.length());
			}
			if (sEtiq.equals("07")) {				
				sContrato = pCuenta.substring(iPos,iPos+iLong);	//Tipo de contrato
				sTContrato = sContrato;
				if(sContrato.equals("AF"))
					sContrato="APARATOS/MUEBLES";
				if(sContrato.equals("AG"))
					sContrato="AGROPECUARIO (PFAE)";
				if(sContrato.equals("AL"))
					sContrato="ARRENDAMIENTO AUTOMOTRIZ";
				if(sContrato.equals("AP"))
					sContrato="AVIACION";
				if(sContrato.equals("AU"))
					sContrato="COMPRA DE AUTOMOVIL";
				if(sContrato.equals("BD"))
					sContrato="FIANZA";
				if(sContrato.equals("BT"))
					sContrato="BOTE/LANCHA";
				if(sContrato.equals("CC"))
					sContrato="TARJETA DE CREDITO";
				if(sContrato.equals("CE"))
					sContrato="CARTAS DE CREDITO (PFAE)";
				if(sContrato.equals("CF"))
					sContrato="CREDITO FISCAL";
				if(sContrato.equals("CL"))
					sContrato="LINEA DE CREDITO";
				if(sContrato.equals("CO"))
					sContrato="CONSOLIDACION";
				if(sContrato.equals("CS"))
					sContrato="CREDITO SIMPLE (PFAE)";
				if(sContrato.equals("CT"))
					sContrato="CON COLATERAL";
				if(sContrato.equals("DE"))
					sContrato="DESCUENTOS (PFAE)";
				if(sContrato.equals("EQ"))
					sContrato="EQUIPO";
				if(sContrato.equals("FI"))
					sContrato="FIDEICOMISOS (PFAE)";
				if(sContrato.equals("FT"))
					sContrato="FACTORAJE";
				if(sContrato.equals("HA"))
					sContrato="HABILITACION O AVIO (PFAE)";
				if(sContrato.equals("HE"))
					sContrato="PRESTAMO TIPO *HOME EQUITY*";
				if(sContrato.equals("HI"))
					sContrato="MEJORAS A LA CASA";
				if(sContrato.equals("LS"))
					sContrato="ARRENDAMIENTO";
				if(sContrato.equals("MI"))
					sContrato="OTROS";
				if(sContrato.equals("OA"))
					sContrato="OTROS ADEUDOS VENCIDOS (PFAE)";
				if(sContrato.equals("PA"))
					sContrato="PRESTAMOS PARA PERSONAS FISICAS CON ACTIVIDAD EMPRESARIAL PFAE)";
				if(sContrato.equals("PB"))
					sContrato="EDITORIAL";
				if(sContrato.equals("PG"))
					sContrato="PGUE (PRESTAMO CON GARANTIAS DE UNIDADES INDUSTRIALES)(PFAE)";
				if(sContrato.equals("PL"))
					sContrato="PRESTAMO PERSONAL";
				if(sContrato.equals("PR"))
					sContrato="PRENDARIO (PFAE)";
				if(sContrato.equals("PQ"))
					sContrato="QUIROGRAFARIO (PFAE)";
				if(sContrato.equals("RC"))
					sContrato="REESTRUCTURADO";
				if(sContrato.equals("RD"))
					sContrato="REDESCUENTO (PFAE)";
				if(sContrato.equals("RE"))
					sContrato="BIENES RAICES";
				if(sContrato.equals("RF"))
					sContrato="REFACCIONARIO (PFAE)";
				if(sContrato.equals("RN"))
					sContrato="RENOVADO (PFAE)";
				if(sContrato.equals("RV"))
					sContrato="VEHICULO RECREATIVO";
				if(sContrato.equals("SC"))
					sContrato="TARJETA GARANTIZADA";
				if(sContrato.equals("SE"))
					sContrato="PRESTAMO GARANTIZADO";
				if(sContrato.equals("SG"))
					sContrato="SEGUROS";
				if(sContrato.equals("SM"))
					sContrato="SEGUNDA HIPOTECA";
				if(sContrato.equals("ST"))
					sContrato="PRESTAMO PARA ESTUDIANTE";
				if(sContrato.equals("TE"))
					sContrato="TARJETA DE CREDITO EMPRESARIAL";
				if(sContrato.equals("UK"))
					sContrato="DESCONOCIDO";
				if(sContrato.equals("US"))
					sContrato="PRESTAMO NO GARANTIZADO";
			}
			if(sEtiq.equalsIgnoreCase("08")){
				sMoneda = pCuenta.substring(iPos,iPos+iLong);  //Moneda
				if(sMoneda != "MX" || sMoneda != "US" || sMoneda != "UD")
					sMoneda ="MX";
			}			
			if (sEtiq.equals("10")) {				
				sNumPagos = pCuenta.substring(iPos,iPos+iLong);	//Numero de pagos
			}
			if (sEtiq.equals("11")) {				
				sFrecPagos = pCuenta.substring(iPos,iPos+iLong); //Fecuencia de pagos
				if(sFrecPagos.equals("B"))
					sFrecPagos = "BIMESTRAL";
				if(sFrecPagos.equals("D"))
					sFrecPagos = "DIARO";
				if(sFrecPagos.equals("H"))
					sFrecPagos = "POR HORA";
				if(sFrecPagos.equals("K"))
					sFrecPagos = "CATORCENAL";
				if(sFrecPagos.equals("M"))
					sFrecPagos = "MENSUAL";
				if(sFrecPagos.equals("P"))
					sFrecPagos = "DEDUCCION DEL SALARIO";
				if(sFrecPagos.equals("Q"))
					sFrecPagos = "TRIMESTRAL";
				if(sFrecPagos.equals("S"))
					sFrecPagos = "QUINCENAL";
				if(sFrecPagos.equals("V"))
					sFrecPagos = "VARIABLE";
				if(sFrecPagos.equals("W"))
					sFrecPagos = "SEMANAL";
				if(sFrecPagos.equals("Y"))
					sFrecPagos = "ANUAL";
				if(sFrecPagos.equals("Z"))
					sFrecPagos = "PAGO MINIMO PARA CUENTAS REVOLVENTES";
			}
			if (sEtiq.equals("12")) {
				sMonto = pCuenta.substring(iPos,iPos+iLong);	//Monto a Pagar
			}
			if (sEtiq.equals("13")) {
				sFecAper = pCuenta.substring(iPos,iPos+iLong);	//Fecha apertura
				fFecAper = convierte_fecha(sFecAper);
			}
			if (sEtiq.equals("14")) {
				sFecUlt = pCuenta.substring(iPos,iPos+iLong);	//Fecha ultimo pago
				fFecUlt = convierte_fecha(sFecUlt);
			}
			if (sEtiq.equals("15")) {
				sFecUltc = pCuenta.substring(iPos,iPos+iLong);	//Fecha ultima compra
				fFecUltc = convierte_fecha(sFecUltc);
			}
			if (sEtiq.equals("16")) {
				sFecCierre = pCuenta.substring(iPos,iPos+iLong);	//Fecha cierre
				fFecCierre = convierte_fecha(sFecCierre);
				System.out.println("esta es la f:" + fFecCierre);
			}
			if (sEtiq.equals("21")) {
				sCremax = pCuenta.substring(iPos,iPos+iLong);	//Saldo Actual
				char car = sCremax.charAt(sCremax.length() - 1);
				if(car == '+'  || car == '-')
				{
					sCremax = pCuenta.substring(iPos,iPos+iLong - 1);
					if(car == '-')
						sCremax = "-" + sCremax;					
				}
			}
			if (sEtiq.equals("22")) {
				sSaldo = pCuenta.substring(iPos,iPos+iLong);	//Saldo Actual
				char car = sSaldo.charAt(sSaldo.length() - 1);
				if(car == '+'  || car == '-')
				{
					sSaldo = pCuenta.substring(iPos,iPos+iLong - 1);
					if(car == '-')
						sSaldo = "-" + sSaldo;					
				}
			}
			if (sEtiq.equals("23")) {
				sLimite = pCuenta.substring(iPos,iPos+iLong);//Saldo Vencido
							}
			if (sEtiq.equals("24")) {
				sVencido = pCuenta.substring(iPos,iPos+iLong);	//Saldo Vencido
			}
			if (sEtiq.equals("25")) {
				sNumVenc = pCuenta.substring(iPos,iPos+iLong);	//Num Pagos Vencidos
			}
			if (sEtiq.equals("26")) {
				sMOP = pCuenta.substring(iPos,iPos+iLong);		//Forma Pago
				if(sMOP.equals("UR"))
					sMOP+= "=CUENTA SIN INFORMACION";
				if(sMOP.equals("00"))
					sMOP+= "=MUY RECIENTE PARA SER INFORMADA";
				if(sMOP.equals("01"))
					sMOP+= "=CUENTA AL CORRIENTE";
				if(sMOP.equals("02"))
					sMOP+= "=ATRASO DE 01 A 29 DIAS";
				if(sMOP.equals("03"))
					sMOP+= "=ATRASO DE 30 A 59 DIAS";
				if(sMOP.equals("04"))
					sMOP+= "=ATRASO DE 60 A 89 DIAS";
				if(sMOP.equals("05"))
					sMOP+= "=ATRASO DE 90 A 119 DIAS";
				if(sMOP.equals("06"))
					sMOP+= "=ATRASO DE 120 A 149 DIAS";
				if(sMOP.equals("07"))
					sMOP+= "=ATRASO DE 150 A 12 MESES";
				if(sMOP.equals("96"))
					sMOP+= "=ATRASO DE 12 MESES";
				if(sMOP.equals("97"))
					sMOP+= "=CUENTA CON DEUDA PARCIAL O TOTAL SIN RECUPERAR";
				if(sMOP.equals("99"))
					sMOP+= "=FRAUDE COMETIDO POR EL CLIENTE";				
			}
			if(sEtiq.equals("27")){
				sHistorial = pCuenta.substring(iPos,iPos+iLong); //Historial
				Logger.getAnonymousLogger().info("Historial = "+ sHistorial);
			}
			if (sEtiq.equals("28")) {
				sFecRec = pCuenta.substring(iPos,iPos+iLong);	//Fecha cierre
				fFecRec = convierte_fecha(sFecRec);
			}
			if(sEtiq.equals("29")){
				sFecAnt = pCuenta.substring(iPos,iPos+iLong);	//Fecha cierre
				fFecAnt = convierte_fecha(sFecAnt);
			}
			if (sEtiq.equals("30")) {
				sObs = pCuenta.substring(iPos,iPos+iLong);		//Observacion
				if(sObs.equals("AD"))
					sObs+="=CUENTA EN DISPUTA";
				if(sObs.equals("CA"))
					sObs+="=CUENTA AL CORRIENTE VENDIDA";
				if(sObs.equals("CC"))
					sObs+="=CUENTA CERRADA POR EL CONSUMIDOR";
				if(sObs.equals("CI"))
					sObs+="=CANCELADA POR INACTIVIDAD";
				if(sObs.equals("CM"))
					sObs+="=CANCELADA POR EL OTORGANTE";
				if(sObs.equals("CL"))
					sObs+="=CUENTA EN COBRANZA PAGADA TOTALMENTE";
				if(sObs.equals("CP"))
					sObs+="=CARTERA VENCIDA";
				if(sObs.equals("CR"))
					sObs+="=DACION EN RENTA";
				if(sObs.equals("CV"))
					sObs+="=CUENTA VENCIDA VENDIDA";
				if(sObs.equals("CZ"))
					sObs+="=CANCELADA CON SALDO CERO";
				if(sObs.equals("DP"))
					sObs+="=PAGOS DIFERIDOS";
				if(sObs.equals("DR"))
					sObs+="=DISPUTA RESUELTA, CONSUMIDOR INCONFORME";
				if(sObs.equals("FD"))
					sObs+="=CUENTA FRAUDULENTA";
				if(sObs.equals("FN"))
					sObs+="=CUENTA FRAUDULENTA NO ATRIBUIBLE AL CONSUMIDOR";
				if(sObs.equals("FP"))
					sObs+="=CANCELACION DE ADJUDICACION DE INMUEBLE POR PAGO";
				if(sObs.equals("FR"))
					sObs+="=ADJUDICACION DE INMUEBLE EN PROCESO";
				if(sObs.equals("IA"))
					sObs+="=CUENTA INACTIVA";
				if(sObs.equals("IR"))
					sObs+="=ADJUDICACION INVOLUNTARIA";
				if(sObs.equals("LC"))
					sObs+="=QUITA POR IMPORTE MENOR ACORDADA CON EL CONSUMIDOR";
				if(sObs.equals("LG"))
					sObs+="=QUITA POR IMPORTE MENOR POR PROGRAMA INSTITUCIONAL";
				if(sObs.equals("LS"))
					sObs+="=TARJETA DE CREDITO EXTRAVIADA O ROBADA";
				if(sObs.equals("MD"))
					sObs+="=PAGO PARCIAL EFECTUADO A CUENTA IRRECUPERABLE";
				if(sObs.equals("NA"))
					sObs+="=CUENTA AL CORRIENTE VENDIDA A UN NO USUARIO DE BC";
				if(sObs.equals("NV"))
					sObs+="=CUENTA VENCIDA VENDIDA A UN NO USUARIO DE BC";
				if(sObs.equals("PC"))
					sObs+="=ENVIADO A DESPACHO DE COBRANZA";
				if(sObs.equals("PD"))
					sObs+="=ADJUDICACION CANCELADA POR PAGO";
				if(sObs.equals("PL"))
					sObs+="=LIMITE EXCEDIDO";
				if(sObs.equals("PS"))
					sObs+="=SUSPENSION DE PAGO";
				if(sObs.equals("RA"))
					sObs+="=CUENTA AL CORRIENTE RESTRUCTURADA POR PROGRAMA INSTITUCIONAL";
				if(sObs.equals("RC"))
					sObs+="=CUENTA AL CORRIENTE RESTRUCTURADA ACORDADA CON EL CONSUMIDOR";
				if(sObs.equals("RE"))
					sObs+="=CUENTA AL CORRIENTE RESTRUCTURADA PAGADA TOTALMENTE";
				if(sObs.equals("RF"))
					sObs+="=REFINANCIADA";
				if(sObs.equals("RO"))
					sObs+="=CUENTA VENCIDAD RESTRUCTURADA POR PROGRAMA INSTITUCIONAL";
				if(sObs.equals("RR"))
					sObs+="=RESTITUCION DEL BIEN";
				if(sObs.equals("RV"))
					sObs+="=CUENTA VENCIDA RESTRUCTURADA ACORDADA CON EL CONSUMIDOR";
				if(sObs.equals("SC"))
					sObs+="=DEMANDA RESUELTA EN FAVOR DEL CONSUMIDOR";
				if(sObs.equals("SG"))
					sObs+="=DEMANDA POR EL OTORGANTE";
				if(sObs.equals("SP"))
					sObs+="=DEMANDA RESUELTA A FAVOR DEL OTORGANTE";
				if(sObs.equals("ST"))
					sObs+="=ACUERDO POR IMPORTE MENOR";
				if(sObs.equals("SU"))
					sObs+="=DEMANDA POR EL CONSUMIDOR";
				if(sObs.equals("TC"))
					sObs+="=SUSTICION DE DEUDOR";
				if(sObs.equals("TL"))
					sObs+="=TRANSFERENCIA A NUEVO OTORGANTE";
				if(sObs.equals("TR"))
					sObs+="=TRANSFERIDA A OTRA AREA";
				if(sObs.equals("UP"))
					sObs+="=CUENTA QUE CAUSA QUEBRANTO";
				if(sObs.equals("VR"))
					sObs+="=DACION EN PAGO";
			}
			if (sEtiq.equals("37")) {
				sFecPeor = pCuenta.substring(iPos,iPos+iLong);		//Fecha peor atraso
				fFecPeor = convierte_fecha(sFecPeor);
			}
			if (sEtiq.equals("38")) {
				sMOP2 = pCuenta.substring(iPos,iPos+iLong);		//Forma Pago
				if(sMOP2.equals("UR"))
					sMOP2+= "=CUENTA SIN INFORMACION";
				if(sMOP2.equals("00"))
					sMOP2+= "=MUY RECIENTE PARA SER INFORMADA";
				if(sMOP2.equals("01"))
					sMOP2+= "=CUENTA AL CORRIENTE";
				if(sMOP2.equals("02"))
					sMOP2+= "=ATRASO DE 01 A 29 DIAS";
				if(sMOP2.equals("03"))
					sMOP2+= "=ATRASO DE 30 A 59 DIAS";
				if(sMOP2.equals("04"))
					sMOP2+= "=ATRASO DE 60 A 89 DIAS";
				if(sMOP2.equals("05"))
					sMOP2+= "=ATRASO DE 90 A 119 DIAS";
				if(sMOP2.equals("06"))
					sMOP2+= "=ATRASO DE 120 A 149 DIAS";
				if(sMOP2.equals("07"))
					sMOP2+= "=ATRASO DE 150 A 12 MESES";
				if(sMOP2.equals("96"))
					sMOP2+= "=ATRASO DE 12 MESES";
				if(sMOP2.equals("97"))
					sMOP2+= "=CUENTA CON DEUDA PARCIAL O TOTAL SIN RECUPERAR";
				if(sMOP2.equals("99"))
					sMOP2+= "=FRAUDE COMETIDO POR EL CLIENTE";	
			}
			if ((sEtiq.equals("TL")) || (sEtiq.equals("IQ"))  || (sEtiq.equals("RS")))  {
				iLong = iPos - 4;
				iPos = xLong;
			}
			iPos += iLong;
			
		}
		if(sLimite == null || sLimite  == ""){
			sLimite = "0";
		}
		Logger.getAnonymousLogger().info(sLimite);

		/*String sInsert = "Insert into Circulo_cuentas(folioconsultaotorgante, fechaactualizacion, registroimpugnado, claveotorgante, nombreotorgante, " +
		"tiporesponsabilidad, tipocuenta, numeropagos, frecuenciapagos, fechaaperturacuenta, fechaultimopago, fechacierrecuenta, " +
		"saldoactual, saldovencido, numeropagosvencidos, historicopagos,claveunidadmonetaria, pagoactual, fechapeoratraso," +
		"saldovencidopeoratraso,limitecredito, montopagar, tipocredito, peoratraso, observacion, creditomaximo, fechaultimacompra) values(" +
		fol + ",'" + fFecAct + "','" + sImp + "','" + clOtor + "','" + nomOtor + 
		"','" + sResp + "','" + sTipo + "'," + sNumPagos + ",'" + sFrecPagos + "','" + fFecAper + "','" + fFecUlt
		+ "','" + fFecCierre + "'," + sSaldo + "," + sVencido + "," + sNumVenc + ",'" + sHistorial + "','" + sMoneda + "','" 
		+ sMOP + "','" + fFecPeor + "','" + sSaldov + "'," + sLimite + "," + sMonto + ",'" + sContrato + "','" + sMOP2 + 
		"','" + sObs +"'," + sCremax +",'" + fFecUltc +"')";*/
		
		String sInsert = "Insert into Circulo_cuentas(folioconsultaotorgante, fechaactualizacion, registroimpugnado, claveotorgante, nombreotorgante, " +
		"tiporesponsabilidad, tipocuenta, numeropagos, frecuenciapagos, fechaaperturacuenta, fechaultimopago, fechacierrecuenta, " +
		"saldoactual, saldovencido, numeropagosvencidos, historicopagos,claveunidadmonetaria, pagoactual, fechapeoratraso," +
		"saldovencidopeoratraso,limitecredito, montopagar, tipocredito, peoratraso, observacion, creditomaximo, fechaultimacompra, " +
		"fecharecientehistoricopagos, fechaantiguahistoricopagos, tipo_credito_id) values(" +
		fol + ",'" + fFecAct + "','" + sImp + "','" + clOtor + "','" + nomOtor + 
		"','" + sResp + "','" + sTipo + "'," + sNumPagos + ",'" + sFrecPagos + "','" + fFecAper + "','" + fFecUlt
		+ "','" + fFecCierre + "'," + sSaldo + "," + sVencido + "," + sNumVenc + ",'" + sHistorial + "','" + sMoneda + "','" 
		+ sMOP + "','" + fFecPeor + "','" + sSaldov + "'," + sLimite + "," + sMonto + ",'" + sContrato + "','" + sMOP2 + 
		"','" + sObs +"'," + sCremax +",'" + fFecUltc +"','" + fFecRec.toString() + "','" + fFecAnt.toString() + "','" + sTContrato.toString() + "')";

		try{
			Logger.getAnonymousLogger().info(sInsert);
			st.execute(sInsert);
		} catch(Exception e) {
			Logger.getAnonymousLogger().info(e.toString());
                        for( int i=0; i!= e.getStackTrace().length; i++) {
                                Logger.getAnonymousLogger().info(e.getStackTrace()[i].toString());
                        }

			iLong = -1;
		} 
		return iLong;
	}	

	private  int lee_consultas(String pConsultas, int fol) {
		String sFecCons = "";
		String sBuro = "";
		String clOtor = "";
		String nomOtor = "";
		String telOtor = "";
		String sContrato = "";
		String sMonto = "";
		String sMoneda = "";
		String sResp = "";
		String sConsumidor = "";
		String sEtiq = "";
		String sLong = "";
		Date fFecCons = new Date(0,9,9);
		Integer iPos = 0;
		Integer iLong = 0;
		int xLong = pConsultas.length();
//		Logger.getAnonymousLogger().info(pDir + " " + xLong);
		while (iPos < xLong) {
			sEtiq = pConsultas.substring(iPos,iPos+2);
			iPos = iPos + 4;
			sLong = pConsultas.substring(iPos-2,iPos);
			iLong = iLong.parseInt(sLong);
			if (sEtiq.equals("IQ")) {
				if (iPos == 4) {
					sFecCons = pConsultas.substring(iPos,iPos+iLong);  //Fecha de Actualizacion
					fFecCons = convierte_fecha(sFecCons);
					sEtiq = "";
				}
			}
			if (sEtiq.equals("00")) {
				sBuro = pConsultas.substring(iPos,iPos+iLong);  	//Impugnado por el cliente
			}
			if (sEtiq.equals("01")) {
				clOtor = pConsultas.substring(iPos,iPos+iLong);  	//Clave Otorgante
			}
			if (sEtiq.equals("02")) {
				nomOtor = pConsultas.substring(iPos,iPos+iLong);  //Nombre Otorgante 
			}
			if (sEtiq.equals("03")) {
				telOtor = pConsultas.substring(iPos,iPos+iLong);  //Telefono Otorgante
			}
			if (sEtiq.equals("04")) {
				sContrato = pConsultas.substring(iPos,iPos+iLong);	//Tipo de contrato
				if(sContrato.equals("AF"))
					sContrato="APARATOS/MUEBLES";
				if(sContrato.equals("AG"))
					sContrato="AGROPECUARIO (PFAE)";
				if(sContrato.equals("AL"))
					sContrato="ARRENDAMIENTO AUTOMOTRIZ";
				if(sContrato.equals("AP"))
					sContrato="AVIACION";
				if(sContrato.equals("AU"))
					sContrato="COMPRA DE AUTOMOVIL";
				if(sContrato.equals("BD"))
					sContrato="FIANZA";
				if(sContrato.equals("BT"))
					sContrato="BOTE/LANCHA";
				if(sContrato.equals("CC"))
					sContrato="TARJETA DE CREDITO";
				if(sContrato.equals("CE"))
					sContrato="CARTAS DE CREDITO (PFAE)";
				if(sContrato.equals("CF"))
					sContrato="CREDITO FISCAL";
				if(sContrato.equals("CL"))
					sContrato="LINEA DE CREDITO";
				if(sContrato.equals("CO"))
					sContrato="CONSOLIDACION";
				if(sContrato.equals("CS"))
					sContrato="CREDITO SIMPLE (PFAE)";
				if(sContrato.equals("CT"))
					sContrato="CON COLATERAL";
				if(sContrato.equals("DE"))
					sContrato="DESCUENTOS (PFAE)";
				if(sContrato.equals("EQ"))
					sContrato="EQUIPO";
				if(sContrato.equals("FI"))
					sContrato="FIDEICOMISOS (PFAE)";
				if(sContrato.equals("FT"))
					sContrato="FACTORAJE";
				if(sContrato.equals("HA"))
					sContrato="HABILITACION O AVIO (PFAE)";
				if(sContrato.equals("HE"))
					sContrato="PRESTAMO TIPO 'HOME EQUITY'";
				if(sContrato.equals("HI"))
					sContrato="MEJORAS A LA CASA";
				if(sContrato.equals("LS"))
					sContrato="ARRENDAMIENTO";
				if(sContrato.equals("MI"))
					sContrato="OTROS";
				if(sContrato.equals("OA"))
					sContrato="OTROS ADEUDOS VENCIDOS (PFAE)";
				if(sContrato.equals("PA"))
					sContrato="PRESTAMOS PARA PERSONAS FISICAS CON ACTIVIDAD EMPRESARIAL PFAE)";
				if(sContrato.equals("PB"))
					sContrato="EDITORIAL";
				if(sContrato.equals("PG"))
					sContrato="PGUE (PRESTAMO CON GARANTIAS DE UNIDADES INDUSTRIALES)(PFAE)";
				if(sContrato.equals("PL"))
					sContrato="PRESTAMO PERSONAL";
				if(sContrato.equals("PR"))
					sContrato="PRENDARIO (PFAE)";
				if(sContrato.equals("PQ"))
					sContrato="QUIROGRAFARIO (PFAE)";
				if(sContrato.equals("RC"))
					sContrato="REESTRUCTURADO";
				if(sContrato.equals("RD"))
					sContrato="REDESCUENTO (PFAE)";
				if(sContrato.equals("RE"))
					sContrato="BIENES RAICES";
				if(sContrato.equals("RF"))
					sContrato="REFACCIONARIO (PFAE)";
				if(sContrato.equals("RN"))
					sContrato="RENOVADO (PFAE)";
				if(sContrato.equals("RV"))
					sContrato="VEHICULO RECREATIVO";
				if(sContrato.equals("SC"))
					sContrato="TARJETA GARANTIZADA";
				if(sContrato.equals("SE"))
					sContrato="PRESTAMO GARANTIZADO";
				if(sContrato.equals("SG"))
					sContrato="SEGUROS";
				if(sContrato.equals("SM"))
					sContrato="SEGUNDA HIPOTECA";
				if(sContrato.equals("ST"))
					sContrato="PRESTAMO PARA ESTUDIANTE";
				if(sContrato.equals("TE"))
					sContrato="TARJETA DE CREDITO EMPRESARIAL";
				if(sContrato.equals("UK"))
					sContrato="DESCONOCIDO";
				if(sContrato.equals("US"))
					sContrato="PRESTAMO NO GARANTIZADO";
			}
			if (sEtiq.equals("05")) {
				sMoneda = pConsultas.substring(iPos,iPos+iLong);	//Moneda
				if(sMoneda != "MX" || sMoneda != "US" || sMoneda != "UD")
					sMoneda ="MX";
			}
			if (sEtiq.equals("06")) {
				sMonto = pConsultas.substring(iPos,iPos+iLong);	//Monto del contrato
			}
			if (sEtiq.equals("07")) {				
				sResp = pConsultas.substring(iPos,iPos+iLong);			//Tipo de Responsabilidad
				if(sResp.equals("I"))
					sResp = "INDIVIDUAL";
				if(sResp.equals("J"))
					sResp = "MANCOMUNADO";
				if(sResp.equals("C"))
					sResp = "OBLIGADO SOLIDARIO";		
			}
			if (sEtiq.equals("08")) {				
				sConsumidor = pConsultas.substring(iPos,iPos+iLong);	//Nuevo  consumidor
			}
			if ((sEtiq.equals("IQ"))  || (sEtiq.equals("RS")))  {
				iLong = iPos - 4;
				iPos = xLong;
			}
			
			iPos += iLong;
		}
		String sInsert = "Insert into circulo_consultas_efectuadas(folioconsultaotorgante, fechaconsulta, claveotorgante, nombreotorgante, tipocredito, importecredito, tiporesponsabilidad, claveunidadmonetaria) values(" +
		fol + ",'" +  fFecCons + "','" + clOtor + "','" + nomOtor + "','" + sContrato + "'," + sMonto  + ",'" + sResp +"','" + sMoneda+ "')";
		try{
			Logger.getAnonymousLogger().info(sInsert);
			st.execute(sInsert);
			
		}
		catch(Exception e){
			Logger.getAnonymousLogger().info(e.toString());
                        for( int i=0; i!= e.getStackTrace().length; i++) {
                                Logger.getAnonymousLogger().info(e.getStackTrace()[i].toString());
                        }
			iLong = -1;
		}

		
		return iLong;
	}	
	
	private  String lee_fin(String cad)
	{
		return cad.substring(cad.length() - 15, cad.length()-6);
	}
	
	private  Date convierte_fecha (String xFecha){
		Date fFecha=new Date(1988,8,8);
		return fFecha.valueOf(xFecha.substring(4,8) + "-" + xFecha.substring(2,4) + "-" + xFecha.substring(0,2));
	}
}

//HISTORIAL DE MODIFICACIONES
//---------------------------------------------------------------
//VERSI�N |   FECHA    |     AUTOR      | DESCRIPCI�N
//---------------------------------------------------------------
//   2.1  | 30/09/2016 | Miguel Aguilar | Se agreg� un replace a la cadena de consulta para que cambie las letras � por N. 