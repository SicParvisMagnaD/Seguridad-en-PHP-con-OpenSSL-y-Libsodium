<?PHP 
include("Barricada.php");
$menu01=" id=\"current\"";

include("VariablesSesiones.php");
include("comunes/utils.php");

include("clases/conexionAdmin.php");
include("clases/objClase.php");
include("clases/obj_CargoReplica.php");

include("comunes/funcionObjeto.php");
include("comunes/getSiafaData.php");

//include("comunes/libreriaBitSocial.php");
include("comunes/librerias_fecha.php");
include("comunes/Libreria_String.php");


$indicadorValue = 3;
$FechaSistema = date("Y-m-d");
$anio_ = date("Y");//Y: una representaci�n de 4 d�gitos del a�o
$mes_ = date("n"); //Representaci�n num�rica de un mes, sin ceros iniciales (de 1 al 12)
$dia_ = date("j"); //Representaci�n num�rica de un mes, sin ceros iniciales (de 1 al 12)

$aprobarDIPRENA = $_REQUEST['aprobarDIPRENA'];

$datosfirmados=array();

 foreach($aprobarDIPRENA as  $key => $value){


 	$consultaTramitesRev = "select * from tramitescargo  where id = '$key'";	
	$sqlQueryTramitesRev = mysql_query($consultaTramitesRev);
	$numCargoRev = mysql_num_rows($sqlQueryTramitesRev);
	
	
	if ($numCargoRev >0){
	
		$regTramitesRev = mysql_fetch_object($sqlQueryTramitesRev);

		$tipoAccionRev = $regTramitesRev->NaturalezaAccion;
		$dependenciaRev = $regTramitesRev->Dependencia;
		$Posicion = $regTramitesRev->Posicion;


		$regJefaturaRRHH = getSiafaData("roles", "Dependencia = $dependenciaRev and ExcepcionRol = 2 and Activo = 1","id");
		$idJefeRRHH = $regJefaturaRRHH->id;


		$ResueltoPersonalNumero = actualizarResueltosPersonal($dependenciaRev, $anio_);
		$NumeroPosicionAprobado = actualizarContadoresPosiciones2($Posicion, $dependenciaRev,  $anio_);
		$NumeroDocumentoAprobado = actualizarContadores($tipoAccionRev, $dependenciaRev,  $anio_);

	
	
	 
	
	 mysql_query("update tramitescargo set Sello3 ='$value',
	 	         UsuarioDiprena = '$Usuario', 
	 	         FechaDiprena = '$FechaSistema',  
	 	         Indicador = $indicadorValue,
	 	         NumeroDocumentoAprobacion = '$NumeroDocumentoAprobado',
	 	         DiaResuelto = $dia_, 
				 MesResuelto = $mes_, 
	 	         AnioResuelto = $anio_,
				 idJefeRRHH = $idJefeRRHH,
				 ResueltoPersonal = '$ResueltoPersonalNumero'
	 	         where id='$key'");
    	///por cada tr�mite que guarda hacer una r�plica

		$idCargoActual = $key;

		 //REPLICA DEL CARGO:1
	 	include("replicas/ReplicaCargo.php");
	 	include("replicas/ReplicaDatosPersonales.php");
	    //R�PLICA DE LOS DATOS PERSONALES: 2
	
		
		$datosFirmar = $datosfirmados['Posicion'].$datosfirmados['Salario'].$datosfirmados['Gast_Representacion'].$datosfirmados['Ocupacion'];
		$datosFirmar2 =  $datosfirmados['ResueltoPersonal'].$datosfirmados['Identificacion'].$datosfirmados['Nombre'].$datosfirmados['SegundoNombre'];
		$datosFirmar3 =  $datosfirmados['Apellido'].$datosfirmados['SegundoApellido'];
		$datos = $datosFirmar.$datosFirmar2.$datosFirmar3;

		include("firmaDiprena.php");
		
	}
	
   }//Fin Foreach olvidart� es imposible, como buen guajir� yo mi falta reconocer�
   
 mysql_close($sql);


//redireccionar("Diprena.php");

?>	