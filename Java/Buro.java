package net.sistacc.ws.buro;

import java.sql.Connection;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.sql.Statement;

import javax.jws.WebMethod;
import javax.jws.WebService;
import javax.naming.InitialContext;
import javax.sql.DataSource;

import net.sistacc.ws.reporte.Reporte;

@WebService()
public class Buro {

	private InitialContext ctx;
	private DataSource datasource;

	private Connection getConn() {
		Connection conn = null;
		try {
			ctx = new InitialContext();
			datasource = (DataSource) ctx.lookup("java:/sistaccDS");
			conn = datasource.getConnection();
		} catch (Exception e) {
			e.printStackTrace();
		}
		
		return conn;
	}         


	@WebMethod
	public long consulta(String solicitante, int noEtapa, String valorEtapa, int ProductoRequerido, String TipoCuenta, 
			String ClaveUnidadMonetaria, double ImporteContrato, int usuario_id_sol, int usuario_id_aut, String computadora, 
			int confirma, String sucursal ) {
		return consultav2(solicitante,noEtapa,valorEtapa,ProductoRequerido,TipoCuenta,ClaveUnidadMonetaria,ImporteContrato,usuario_id_sol,
				usuario_id_aut, computadora,confirma,sucursal,null);
	}
	
	@WebMethod
	public long consultav2(String solicitante, int noEtapa, String valorEtapa, int ProductoRequerido, String TipoCuenta, 
			String ClaveUnidadMonetaria, double ImporteContrato, int usuario_id_sol, int usuario_id_aut, String computadora, 
			int confirma, String sucursal, String buro ) { 
		Connection conn = null;
		Statement st = null;
		ResultSet res = null;
		long folio = 0;
		
		try {
			conn = getConn();
			
			if (buro == null) {
				st = conn.createStatement();
				res = st.executeQuery("SELECT t_persona FROM solicitante WHERE numero = " + solicitante);
				
				if (res.next()) {
					buro = res.getString("t_persona");
				}
			}
			
			if ("F".equals(buro)) {
				// Consulta PF
				System.out.println( "consultapf put solicitante " + solicitante );
				BuroPFImpl client = new BuroPFImpl();    	
		        folio = client.consulta(conn, solicitante, noEtapa, valorEtapa, ProductoRequerido, TipoCuenta, ClaveUnidadMonetaria, ImporteContrato, usuario_id_sol, usuario_id_aut, computadora, confirma, sucursal);
			} else {
				// Consulta PFAE, PM
				System.out.println( "consultaPM put solicitante " + solicitante );
				BuroPMImpl client = new BuroPMImpl();
		        folio = client.consulta(conn, solicitante, noEtapa, valorEtapa, ProductoRequerido, TipoCuenta, ClaveUnidadMonetaria, ImporteContrato, usuario_id_sol, usuario_id_aut, computadora, confirma, sucursal);
			}
		} catch (Exception e) {
			e.printStackTrace();
			System.out.println(e);
		} finally {
			if (conn != null) {
				try { conn.close(); } 
				catch (SQLException e) { e.printStackTrace(); }
			}
		}
		
        return folio;
	}
	
	@WebMethod
	public int respuestaPF(String folio) {
		Connection conn = null;
		int resultado = -1;
		
		try {
			conn = getConn();
			BuroPFImpl client = new BuroPFImpl();
			resultado = client.respuesta(conn, folio);
		} catch (Exception e) {
			e.printStackTrace();
			System.out.println(e);
		} finally {
			if (conn != null) {
				try { conn.close(); } 
				catch (SQLException e) { e.printStackTrace(); }
			}
		}
		
		return resultado;
	}

	@WebMethod
	public byte[] respuestaPM(String folio) {
		Connection conn = null;
		int resultado = -1;
		
		try {
			conn = getConn();
			BuroPMImpl client = new BuroPMImpl();
			resultado = client.respuesta(getConn(), folio);
			
			// Ejecutamos reporte si corresponde
			if (resultado > 0) {
				Reporte rep = new Reporte();
				if (rep.consultaBuro(folio))
					return rep.getContenidoReporte();
			}
		} catch (Exception e) {
			e.printStackTrace();
		} finally {
			if (conn != null) {
				try { conn.close(); } 
				catch (SQLException e) { e.printStackTrace(); }
			}
		}
		
		return null;
	}

	@WebMethod
	public byte[] reporteConsultaPM(String folio) {
		Reporte rep = new Reporte();
		byte[] contenidoReporte = null;
		
		try {
			if ("".equals(folio) || ! this.comprobarFolio(folio))
				return null;
			
			if (rep.consultaBuro(folio))
				contenidoReporte = rep.getContenidoReporte();
		} catch (Exception e) {
			e.printStackTrace();
			System.out.println(e);
		} 
		
		return contenidoReporte;
	}
	
	@WebMethod
	public long autenticador(String Solicitante,
			String ClaveUnidadMonetaria,
			double ImporteContrato, 
			int usuario_id_sol,
			int usuario_id_aut, 
			String computadora, 
			int confirma, 
			String sucursal, 
			String sValores) {
		BuroPFAutenticador buro = new BuroPFAutenticador();
		long folio = buro.consulta(getConn() ,Solicitante, ClaveUnidadMonetaria, ImporteContrato, usuario_id_sol, usuario_id_aut, computadora, confirma, sucursal, sValores);
		return folio;		
	}

	@WebMethod
	public int califica(int iFolio) {
		Connection conn = null;
		String resultado = "";
		int iResult =0;
		try {
			conn = getConn();
			Statement st = conn.createStatement();
			ResultSet res2 = st.executeQuery("select buro_califica_cuentas(" + iFolio + ")");
			res2.next();
			iResult = res2.getInt(1);
			if (iResult > 0){
				res2 = st.executeQuery("select coalesce(autorizar,'') from cat_resultado where resultado_id = " + iResult);
				res2.next();
				if (res2.getString(1) == null)
					return -1;
				resultado = res2.getString(1);
				if (resultado.compareTo("S") == 0)
					return 1;
				else {
					if (resultado.compareTo("A") == 0)
						return 2;
					else
						return 0;
				}
			} else
				return -1;
		} catch( Exception se) {
			se.printStackTrace();
		}  finally{
			 try { conn.close(); } catch( Exception e ) {}
		}
		return -1;
	}
	
	private boolean comprobarFolio(String folio) {
		Connection conn = null;
		Statement st = null;
		ResultSet res = null;
		
		try {
			conn = getConn();
			st = conn.createStatement();
			res = st.executeQuery("SELECT COUNT(*) AS existe FROM consultas_circulo WHERE folioconsulta = '" + folio + "'");
			
			if (! res.next()) return false;
			return (res.getInt("existe") > 0);
		} catch (Exception e) {
			return false;
		} finally {
			if (conn != null) {
				try { conn.close(); } 
				catch (SQLException e) { e.printStackTrace(); }
			}
		}
	}
}
