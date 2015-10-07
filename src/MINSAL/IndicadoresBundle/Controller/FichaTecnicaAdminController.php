<?php

namespace MINSAL\IndicadoresBundle\Controller;

use Sonata\AdminBundle\Controller\CRUDController as Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

//use Symfony\Component\Console\Input\ArrayInput;

class FichaTecnicaAdminController extends Controller
{
    public function editAction($id = null)
    {
        $repo = $this->getDoctrine()->getManager()->getRepository('IndicadoresBundle:FichaTecnica');
        $this->admin->setRepository($repo);

        return parent::editAction($id);
    }

    public function createAction()
    {
        $repo = $this->getDoctrine()->getManager()->getRepository('IndicadoresBundle:FichaTecnica');
        $this->admin->setRepository($repo);

        return parent::createAction();
    }
	
	public function getListadoIndicadores(){
		$user = $this->container->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();
        $clasificacionUso = $em->getRepository("IndicadoresBundle:ClasificacionUso")->findAll();

        //Luego agregar un método para obtener la clasificacion de uso por defecto del usuario
        $usuario = $this->getUser();
        if ($usuario->getClasificacionUso()) {
            $clasificacionUsoPorDefecto = $usuario->getClasificacionUso();
        } 
		else 
		{
            $clasificacionUsoPorDefecto = $clasificacionUso[0];
        }
        $categorias = $em->getRepository("IndicadoresBundle:ClasificacionTecnica")->findBy(array('clasificacionUso' => $clasificacionUsoPorDefecto));

        //Salas por usuario
        $usuarioSalas = array();
        if (($usuario->hasRole('ROLE_SUPER_ADMIN')))
		{
            foreach ($em->createQuery('SELECT g FROM IndicadoresBundle:GrupoIndicadores g ORDER BY g.nombre ASC')->getResult() as $sala) 	
			{
                $usuarioSalas[$sala->getId()] = $sala;
            } 
        }
		else
		{
           foreach ($usuario->getGruposIndicadores() as $sala) 
		   {
                $usuarioSalas[$sala->getGrupoIndicadores()->getId()] = $sala->getGrupoIndicadores();
           } 
        }
		
		$salasXusuario=array();
		$i=0;
		foreach ($usuarioSalas as $sala) {
			$esduenio=$em->createQuery("SELECT u.esDuenio FROM IndicadoresBundle:UsuarioGrupoIndicadores u WHERE u.usuario='".$user->getId()."' and u.grupoIndicadores='".$sala->getId()."'")->getResult();
			if($esduenio)
				if($esduenio[0]["esDuenio"])
				{
					$salasXusuario[$i]['datos_sala'] = $sala;
					$salasXusuario[$i]['indicadores_sala'] = $em->getRepository('IndicadoresBundle:GrupoIndicadores')
						->getIndicadoresSala($sala);
					$i++;
				}
        }
		
        //Salas asignadas al grupo al que pertenece el usuario
		$salasXgrupoTemp=array();
        foreach ($usuario->getGroups() as $grp)
		{
            foreach ($grp->getSalas() as $sala)
			{
                $usuarioSalas[$sala->getId()] = $sala;
				$salasXgrupoTemp[]=$sala;
            }
        }
        $salasXgrupo=array();
		$i=0;
		
		$uXg=$em->getRepository('IndicadoresBundle:GrupoIndicadores')->getSalaGrupo($user->getId());
		foreach ($uXg as $sala) 
		{
			$id=$sala["grupoindicadores_id"];
			$sala=$em->createQuery("SELECT g FROM IndicadoresBundle:GrupoIndicadores g WHERE g='$id' ORDER BY g.nombre ASC")->getResult();
			$salasXgrupo[$i]['datos_sala'] = $sala[0];
			$salasXgrupo[$i]['indicadores_sala'] = $em->getRepository('IndicadoresBundle:GrupoIndicadores')
					->getIndicadoresSala($sala[0]);
			$i++;
		}
		
        $i = 0;
        $salas = array();
        foreach ($usuarioSalas as $sala) {
            $salas[$i]['datos_sala'] = $sala;
            $salas[$i]['indicadores_sala'] = $em->getRepository('IndicadoresBundle:GrupoIndicadores')
                    ->getIndicadoresSala($sala);
            $i++;
        }

        //Indicadores asignados por usuario
        $usuarioIndicadores = ($usuario->hasRole('ROLE_SUPER_ADMIN')) ?
                //$em->getRepository("IndicadoresBundle:FichaTecnica")->findAll() :
                $this->get('doctrine')->getManager()->createQuery('SELECT c FROM IndicadoresBundle:FichaTecnica c ORDER BY c.id,c.nombre ASC')->getResult() :
                $usuario->getIndicadores();
        //Indicadores asignadas al grupo al que pertenece el usuario
        $indicadoresPorGrupo = array();
        foreach ($usuario->getGroups() as $grp){            
            foreach ($grp->getIndicadores() as $indicadores_grupo){
                $indicadoresPorGrupo[] = $indicadores_grupo;
            }
        }
        
        $indicadores_por_usuario = array();
        $indicadores_clasificados = array();
        foreach ($usuarioIndicadores as $ind) {
            $indicadores_por_usuario[] = $ind->getId();
        }
        
        foreach ($indicadoresPorGrupo as $ind){
            $indicadores_por_usuario[] = $ind->getId();
        }
        
        $categorias_indicador = array();
        foreach ($categorias as $cat) {
            $categorias_indicador[$cat->getId()]['cat'] = $cat;
            $categorias_indicador[$cat->getId()]['indicadores'] = array();
            $indicadores_por_categoria = $cat->getIndicadores();
            foreach ($indicadores_por_categoria as $ind) {
                if (in_array($ind->getId(), $indicadores_por_usuario)) {
                    $categorias_indicador[$cat->getId()]['indicadores'][] = $ind;
                    $indicadores_clasificados[] = $ind->getId();
                }
            }
        }

        $indicadores_no_clasificados = array();
        foreach ($usuarioIndicadores as $ind) {
            if (!in_array($ind->getId(), $indicadores_clasificados)) {
                $indicadores_no_clasificados[] = $ind;
            }
        }
        foreach ($indicadoresPorGrupo as $ind) {
            if (!in_array($ind->getId(), $indicadores_clasificados)) {
                $indicadores_no_clasificados[] = $ind;
            }
        }
        $resp= array('categorias' => $categorias_indicador,
                    'clasificacionUso' => $clasificacionUso,
                    'salas' => $salas,
					'salasXusuario' => $salasXusuario,
					'salasXgrupo' => $salasXgrupo,
                    'indicadores_no_clasificados' => $indicadores_no_clasificados);
        
        return $resp;
    }
	
    public function tableroAction()
    {		
        $datos = $this->getListadoIndicadores();
		
        return $this->render('IndicadoresBundle:FichaTecnicaAdmin:tablero.html.twig', array(
                    'categorias' => $datos['categorias'],
                    'clasificacionUso' => $datos['clasificacionUso'],
                    'salas' => $datos['salas'],
					'salasXusuario' => $datos['salasXusuario'],
					'salasXgrupo' => $datos['salasXgrupo'],
                    'indicadores_no_clasificados' => $datos['indicadores_no_clasificados']
        ));
    }

    public function CubosAction()
    {
        return $this->render('IndicadoresBundle:FichaTecnicaAdmin:cubos.html.twig', array());
    }
    
	public function PivotTableAction()
    {        
        $datos = $this->getListadoIndicadores();
        
        return $this->render('IndicadoresBundle:FichaTecnicaAdmin:pivotTable.html.twig', array(
                    'categorias' => $datos['categorias'],
                    'clasificacionUso' => $datos['clasificacionUso'],
                    'indicadores_no_clasificados' => $datos['indicadores_no_clasificados']
        ));
    }
    /*
    Mostrar Reporte Gerenciales generados por Pentaho
    */
    public function reporteAction() {

        /*$id = $this->getRequest()->get('id');
        $reporte= "http://etab.salud.gob.sv:8080/pentaho/content/reporting/reportviewer/report.html?solution=reportes&path=&name=indicador".$id.".prpt";
        return new RedirectResponse($reporte);
        */
		$req = $this->getRequest();
		$em = $this->getDoctrine()->getManager();
        $id = $this->getRequest()->get('id');
		$indicador = $em->find('IndicadoresBundle:FichaTecnica', $id);
		$vars=array();
        
		foreach($indicador->getVariables() as $var){
			$lectura = $em->getRepository('IndicadoresBundle:OrigenDatos')->getUltimaActualizacion($var->getOrigenDatos());
			array_push($vars,array('lectura'=>$lectura,'valor'=>$var->getIniciales().": ".$var->getNombre()));
        }            
		$CDAFile=$this->admin->getConfigurationPool()->getContainer()->getParameter('carpeta_pentaho_cda')."indicador".$id.".cda";

		if(!file_exists($CDAFile))
		{
			$this->admin->crearPentahoCDA($indicador);
		}
	
        $em->flush();
        return $this->render('IndicadoresBundle:FichaTecnicaAdmin:reporte.html.twig',
		array('id'=>$id, 'nombre'=>$indicador->getNombre(),
                        'inter'=>$indicador->getConcepto(),
                        'tema'=>$indicador->getTema(),
			'vars'=>$vars,
			'fecha'=>date("Y-m-d H:i")));
    } 


    public function batchActionVerFicha($idx = null)
    {
        $parameterBag = $this->get('request')->request;
        $em = $this->getDoctrine()->getManager();

        $selecciones = $parameterBag->get('idx');

        $salida = '';
        foreach ($selecciones as $ficha) {
            $fichaTec = $em->find('IndicadoresBundle:FichaTecnica', $ficha);

            $admin = $this->get('sonata.admin.ficha');
            $admin->setSubject($fichaTec);

            $html = $this->render($admin->getTemplate('show'), array(
                'action' => 'show',
                'object' => $fichaTec,
                'elements' => $admin->getShow(),
                'admin' => $admin,
                'base_template' => 'IndicadoresBundle::ajax_layout.html.twig'
            ));

            $salida .= $html->getContent() . '<BR /><BR />';
        }
        //Quitar los comentarios del código html, enlaces y aplicar estilos
        $salida = preg_replace('/<!--(.|\s)*?-->/', '', $salida);
        $salida = preg_replace('/<a(.|\s)*?>/', '', $salida);
        $salida = str_ireplace('</a>', '', $salida);
        $salida = str_ireplace('TD',"TD STYLE='border: 2px double black'", $salida);
        $salida = str_ireplace('TH',"TH STYLE='border: 2px double black'", $salida);
        $salida = str_ireplace('<TABLE',"<TABLE width=95% ", $salida);

        return new Response('<HTML>'.$salida.'</HTML>', 200, array(
            'Content-Type' => 'application/msword',
            'Content-Disposition' => 'attachment; filename="ficha_tecnica.doc"',
            'Pragma' => 'no-cache',
            'Expires' => '0'
            )
        );
    }

/**
     * @Route("/tablero/sala/{sala}", name="tablero_sala", options={"expose"=true})
     */
    public function tableroSalaAction($sala, Request $request) {
        $em = $this->getDoctrine()->getManager();
        $redis = $this->container->get('snc_redis.default');

        $recalcular = true;
        $tipo_reporte = ($request->get('indicador') != null) ? 'indicador' : 'sala';

        //Verificar si existe la información de la sala almacenada en caché
        if ($redis->get('sala_time_' . $tipo_reporte . '_' . $sala) != null) {
            //Verificar la fecha de la última actualización
            $ult_fecha = new \DateTime("2000-07-08 11:14:15.638276");
            $salaObj = $em->getRepository("IndicadoresBundle:GrupoIndicadores")->find($sala);
            $indicadores_sala = $em->getRepository('IndicadoresBundle:GrupoIndicadores')->getIndicadoresSala($salaObj);
            foreach ($indicadores_sala as $ind) {
                $indicador = $indicador = $em->find('IndicadoresBundle:FichaTecnica', $ind['idIndicador']);
                if ($indicador->getUltimaLectura() > $ult_fecha) {
                    $ult_fecha = $indicador->getUltimaLectura();
                }
            }
            //Comparar con la fecha de actualización de la sala
            $ult_fecha = ($ult_fecha > $salaObj->getUpdatedAt()) ? $ult_fecha : $salaObj->getUpdatedAt();

            //Recuperar la última fecha en que se construyó el informe
            $st = $redis->get('sala_time_' . $tipo_reporte . '_' . $sala);
            $dt = new \DateTime();
            $dt->setTimestamp($st);
            $recalcular = ($ult_fecha > $dt) ? true : false;
        }
        $recalcular = true;
        if ($recalcular) {
            $html = $this->tableroAction($sala);
            $info_indicador = '';
            if ($request->get('indicador') != null) {
                //Se está cargando el reporte de la sala como reporte asociado
                //a un indicadores, recuperar el indicador para mostrar 
                //información adicional

                $id = $request->get('indicador');
                $indicador = $em->find('IndicadoresBundle:FichaTecnica', $id);
                $info_indicador .= '<BR/></BR/><BR/></BR/>'
                        . '<DIV class="col-md-12" >'
                        . '<B>Interpretación:</B><BR/>' . $indicador->getTema()
                        . '</DIV><BR/><BR/>'
                        . '<DIV class="col-md-12" >'
                        . '<B>Concepto:</B></BR>' . $indicador->getConcepto()
                        . '</DIV><BR/><BR/>'
                        . '<DIV class="col-md-12" >'
                        . '<B>Observaciones:</B><BR/>' . $indicador->getObservacion()
                        . '</div>'
                ;
            }

            $html = preg_replace("/HTTP.+/", "", $html);
            $html = preg_replace("/Cache.+/", "", $html);
            $html = preg_replace("/Date.+/", "", $html);

            $http = 'http';
            if (array_key_exists('HTTPS', $_SERVER)) {
                $http = ($_SERVER['HTTPS'] == null or $_SERVER['HTTPS'] == 'off') ? 'http' : 'https';
            }
            $html = str_replace(array('href="/bundles', 'src="/bundles', 'src="/app_dev.php'), 
                    array('href="' . $http . '://' . $_SERVER['HTTP_HOST'] . $this->container->getParameter('directorio') . '/bundles',
                            'src="' . $http . '://' . $_SERVER['HTTP_HOST']. $this->container->getParameter('directorio') . '/bundles',
                            'src="' . $http . '://'. $_SERVER['HTTP_HOST'] . $this->container->getParameter('directorio') . '/app_dev.php'), $html);
            $html .= $info_indicador;
            $html = $this->get('knp_snappy.pdf')->getOutputFromHtml($html);
            
            $redis->set('sala_' . $sala, $html);
            $redis->set('sala_' . $tipo_reporte . '_' . $sala, $html);
            $redis->set('sala_time_' . $tipo_reporte . '_' . $sala, time());
        } else {
            $html = $redis->get('sala_' . $sala);
        }

        //return new Response($html);        
        return new Response(
                $html, 200, array(
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="reporte.pdf"'
                )
        );
    }	
}
