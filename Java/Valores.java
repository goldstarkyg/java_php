package net.sistacc.ws.buro;

public class Valores {
	
	//valores para Circulo
	private String tipoCuenta; //Tipo Cuenta (Anexo Tipo de Cuenta)
	private String cveMoneda;  //Clave Unidad Monetaria
	private double mtoContrato;  //Monto a solicitar
	private int numFirma;  //Numero de Firma (numero_firma de solicitante)
	private String aPaterno;
	private String aMaterno;
	private String nombre;
	private String fecNacimiento;
	private String domicilio;
	private String colonia;
	private String ciudad;
	private String municipio;
	private String estado;
	private String cp;
	private String telefono;
	private String folioConsulta;
	private String autenticacion;
	private String numSolicitud; //Es el identificador de la autenticación por parte del Otorgante
	private String tarjetaTiene;
	private String tarjetaNum;
	private String hipotecarioTiene;
	private String automotrizTiene;
	private String responsabilidad;
	private String contrato;
	
	//valores para buro
	private String referencia;
	private String rfc;
	
	
	public String getRfc() {
		return rfc;
	}
	public void setRfc(String rfc) {
		this.rfc = rfc;
	}
	public String getReferencia() {
		return referencia;
	}
	public void setReferencia(String referencia) {
		this.referencia = referencia;
	}
	public String getTarjetaTiene() {
		return tarjetaTiene;
	}
	public void setTarjetaTiene(String tarjetaTiene) {
		this.tarjetaTiene = tarjetaTiene;
	}
	public String getTarjetaNum() {
		return tarjetaNum;
	}
	public void setTarjetaNum(String tarjetaNum) {
		this.tarjetaNum = tarjetaNum;
	}
	public String getHipotecarioTiene() {
		return hipotecarioTiene;
	}
	public void setHipotecarioTiene(String hipotecarioTiene) {
		this.hipotecarioTiene = hipotecarioTiene;
	}
	public String getAutomotrizTiene() {
		return automotrizTiene;
	}
	public void setAutomotrizTiene(String automotrizTiene) {
		this.automotrizTiene = automotrizTiene;
	}
	public String getFolioConsulta() {
		return folioConsulta;
	}
	public void setFolioConsulta(String folioConsulta) {
		this.folioConsulta = folioConsulta;
	}
	public String getAutenticacion() {
		return autenticacion;
	}
	public void setAutenticacion(String autenticacion) {
		this.autenticacion = autenticacion;
	}
	public String getNumSolicitud() {
		return numSolicitud;
	}
	public void setNumSolicitud(String numSolicitud) {
		this.numSolicitud = numSolicitud;
	}
	public String getTelefono() {
		return telefono;
	}
	public void setTelefono(String telefono) {
		this.telefono = telefono;
	}
	public String getColonia() {
		return colonia;
	}
	public void setColonia(String colonia) {
		this.colonia = colonia;
	}
	public String getMunicipio() {
		return municipio;
	}
	public void setMunicipio(String municipio) {
		this.municipio = municipio;
	}
	public String getEstado() {
		return estado;
	}
	public void setEstado(String estado) {
		this.estado = estado;
	}
	public String getCp() {
		return cp;
	}
	public void setCp(String cp) {
		this.cp = cp;
	}
	public String getFecNacimiento() {
		return fecNacimiento;
	}
	public void setFecNacimiento(String fecNacimiento) {
		this.fecNacimiento = fecNacimiento;
	}
	public String getaPaterno() {
		return aPaterno;
	}
	public void setaPaterno(String aPaterno) {
		this.aPaterno = aPaterno;
	}
	public String getaMaterno() {
		return aMaterno;
	}
	public void setaMaterno(String aMaterno) {
		this.aMaterno = aMaterno;
	}
	public int getNumFirma() {
		return numFirma;
	}
	public void setNumFirma(int numFirma) {
		this.numFirma = numFirma;
	}
	public double getMtoContrato() {
		return mtoContrato;
	}
	public void setMtoContrato(double mtoContrato) {
		this.mtoContrato = mtoContrato;
	}
	public String getCveMoneda() {
		return cveMoneda;
	}
	public void setCveMoneda(String cveMoneda) {
		this.cveMoneda = cveMoneda;
	}
	public String getTipoCuenta() {
		return tipoCuenta;
	}
	public void setTipoCuenta(String tipoCuenta) {
		this.tipoCuenta = tipoCuenta;
	}
	public String getDomicilio() {
		return domicilio;
	}
	public void setDomicilio(String domicilio) {
		this.domicilio = domicilio;
	}	
	
	public String getNombre() {
		return nombre;
	}
	
	public void setNombre(String nombre) {
		this.nombre = nombre;
	}
	public String getCiudad() {
		return ciudad;
	}
	public void setCiudad(String ciudad) {
		this.ciudad = ciudad;
	}
	public String getResponsabilidad() {
		return responsabilidad;
	}
	public void setResponsabilidad(String responsabilidad) {
		this.responsabilidad = responsabilidad;
	}
	public String getContrato() {
		return contrato;
	}
	public void setContrato(String contrato) {
		this.contrato = contrato;
	}
	 
}
