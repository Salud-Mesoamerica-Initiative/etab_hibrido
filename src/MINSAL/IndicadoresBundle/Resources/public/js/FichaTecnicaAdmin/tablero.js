
$(document).ready(function() {
    // *****************
    //Con esto se verifica el comportamiento del area de gráfico
    //Si se despliega algún menú dentro del gráfico se modifica un atributo
    //ccs para que se muestre correctamente se regresa a su modo normal cuando el menú se cierra
    //para esto fue necesario reescribir unos métodos de jQuery
	var cookies = document.cookie.split(";");
	for(var i=0; i < cookies.length; i++) {
		var equals = cookies[i].indexOf("=");
		var name = equals > -1 ? cookies[i].substr(0, equals) : cookies[i];		
		document.cookie = name + "=;expires=Thu, 01 Jan 1970 00:00:00 GMT";
	}
						
    (function(){
        var methods = ["addClass", "toggleClass", "removeClass"]; //métodos a sobreescribir
        $.map(methods, function(method){
            var originalMethod = $.fn[ method ];            
            $.fn[ method ] = function(){                
                var result = originalMethod.apply( this, arguments ); // Execute the original method.                
                myfunction(this); // call your function                
                return result; // return the original result
            };
        });
    })();
    
    function myfunction(obj){
       /* if($(obj).hasClass('sobre_div'))
            if ($(obj).hasClass('open'))
                $('.zona_actual').css('overflow-y','scroll');            
            else
                $('.area_grafico').filter(function(){ return $(this).css('overflow-y') === 'visible';}).css('overflow-y','scroll');        */
    }    
    // *****************
    
    jQuery(document).ajaxStart(function() {
        $('#div_carga').show();
    }).ajaxStop(function() {
        $('#div_carga').hide();
    });
            
    $( "#sala" ).sortable({
        handle: '.titulo',                
    });
    $( "#sala" ).disableSelection();
    $("#mimodal").on('click',function(e) {		
		marcar_agregados();
    }); 
    $('.indicador').on('click',function(e) {
		mid=$(this).attr("data-id");
		cargar_indicador(mid);
    });
	$("#_cerrar_sala_").on('click',function(e) {
        $("#sala").html('');
			$('#nombre_sala').attr('id-sala', '');
			$('#nombre_sala').val('');
			$('#header_sala').html('');
			$("#_cerrar_sala_").attr("style","display:none");
			$("#_guardar_sala_").attr("style","display:none");
			$("#titulo_header").attr("style","display:none");
			
			$("#myModalMenu li button").removeClass("active");
			$("#myModalMenu li button").removeClass("btn-success");	
			$("#myModalMenu li i").removeClass('glyphicon-ok');		
			$("#myModalMenu li i").addClass('glyphicon-plus');
			
			$("#myModalSalas li button").removeClass("active");
			$("#myModalSalas li button").removeClass("btn-success");
			$("#myModalSalas li i").removeClass('glyphicon-ok');			
			$("#myModalSalas li i").addClass('glyphicon-plus');
    });
	$('.salas-id').on('click',function(e) {
		titulo=$(this).attr('sala-nombre');
		sala=$(this).attr('sala-id');
		var id=this.id.split("_");
		id=id[1];
		if($("#a_"+id).hasClass("active")||$("#b_"+id).hasClass("active")||$("#c_"+id).hasClass("active"))
		{  
			$("#a_"+id).removeClass("active");
			$("#b_"+id).removeClass("active");
			$("#c_"+id).removeClass("active");
			
			$("#a_"+id).removeClass("btn-success");	
			$("#b_"+id).removeClass("btn-success");	
			$("#c_"+id).removeClass("btn-success");		
				
			$("#a_"+id).html('<i class="glyphicon glyphicon-plus"></i>');
			$("#b_"+id).html('<i class="glyphicon glyphicon-plus"></i>');
			$("#c_"+id).html('<i class="glyphicon glyphicon-plus"></i>');
			$("#sala").html('');
			$('#nombre_sala').attr('id-sala', '');
			$('#nombre_sala').val('');
			$('#header_sala').html('');
			$("#_cerrar_sala_").attr("style","display:none");
			$("#_guardar_sala_").attr("style","display:none");
			$("#titulo_header").attr("style","display:none");
			
			graficos=Array();
		}
		else
		{
			$("#sala").html('');
			$("#_cerrar_sala_").attr("style","display:");
			$("#_guardar_sala_").attr("style","display:");
			$("#titulo_header").attr("style","display:");
			$('.salas-id').removeClass("btn-success");
			$('.salas-id').removeClass("active");
			$('.salas-id').html('<i class="glyphicon glyphicon-plus"></i>');
			
			$("#a_"+id).addClass("active btn-success");
			$("#b_"+id).addClass("active btn-success");
			$("#c_"+id).addClass("active btn-success");
			
			$("#a_"+id).html('<i class="glyphicon glyphicon-ok"></i>');
			$("#b_"+id).html('<i class="glyphicon glyphicon-ok"></i>');
			$("#c_"+id).html('<i class="glyphicon glyphicon-ok"></i>');
			
			$('#nombre_sala').attr('id-sala', sala);
			$('#nombre_sala').val(titulo);
			$('#header_sala').html('<span class="glyphicon glyphicon-th"></span> ' + titulo );
	
			var graficos = JSON.parse($(this).attr('data'));
			for (i = 0; i < graficos.length; i++) 
			{
				sala_agregar_fila();
				recuperarDimensiones(graficos[i].idIndicador, graficos[i]);
			}						
		}
    });   
    
    $('#agregar_fila').on('click',function(e) {
        sala_agregar_fila();        
    });
	
	$('#quitar_fila').on('click',function(e) {
        sala_quitar_fila();        
    });

    $('#quitar_indicador').on('click',function(e) {
        limpiarZona2($('DIV.zona_actual').attr('id'));
    });

    
    $('DIV.area_grafico').on('click',function(e) {
        zona_elegir(this);
    });

	$('#elimina_sala').on('click',function(e) {
		 var datos_sala = new Object();
		 sala=$('#nombre_sala').val();
		 datos_sala.nombre = $('#nombre_sala').val();
         datos_sala.id = $('#nombre_sala').attr('id-sala');
		 var id=$(this).attr("data-id");
		 a=0;
		 
		 $.getJSON(Routing.generate('sala_eliminar'), {datos: JSON.stringify(datos_sala)},
		 function(resp) 
		 {
			$("#info_sala2").attr("style","display:");			
			if (resp.estado === 'ok') 
			{
				$('#nombre_sala').attr('id-sala', "");
				$('#header_sala').html('');
				$('#info_sala2').html('Se elimino la sala '+sala).addClass('alert-danger');
				$('#myModal').modal('toggle');
				
				$('#salax').each(function() {
					var valor=$(this).find('a').text();
					valor=valor.split(")");
					for(a=0;a<valor.length-1;a++)
					if (valor[a] != '') 
					{
						var val=valor[a].split("(");				
						var otro=parseInt(val[1]);
						if(otro!=0)
						{					
							$("#salax li:nth-child("+(a+1)+") a").text(val[0]+" ("+(otro-1)+")");
						}
					}
				 });
				
				$("#n_"+id).remove();
				$("#u_"+id).remove();
				$("#g_"+id).remove();								
			}
			else 
			{
				$('#info_sala2').html('_error_guardar_sala_').addClass('alert-danger');
			}
			setTimeout(function() {
				$("#info_sala2").toggle('explode',3000);				
				$("#info_sala2").removeClass('alert-success').removeClass('alert-danger').html("");
				$("#info_sala2").attr("style","display:none");
			}, 3000);
		});
	});
    $('#guardar_sala').on('click',function(e) {
        var arreglo_indicadores = [];
        var datos_sala = new Object();

        var nombre_sala = $('#nombre_sala').val();
        if (nombre_sala === '') {
            alert('Ingrese un nombre de sala');
            return;
        }
        var i = 0;
        var posicion = 1;
        $('.area_grafico').each(function() {
            if ($(this).find('.titulo_indicador').html() !== '') {
                var datos = new Object();
                var elementos = [];
                $('#'+ $(this).attr('id') +' .capa_dimension_valores input:checked').each(function() {
                    elementos.push($(this).val());
                }); 
                
                datos.id_indicador = $(this).find('.titulo_indicador').attr('data-id');
                datos.filtros = $(this).find('.filtros_dimensiones').attr('data');
                datos.filtro_desde = $('#'+$(this).attr('id')+' .filtro_desde').val();
                datos.filtro_hasta = $('#'+$(this).attr('id')+' .filtro_hasta').val();
                datos.filtro_elementos = elementos.toString();
                datos.dimension = $('#' + $(this).attr('id') + ' .dimensiones').val();
                datos.tipo_grafico = $('#' + $(this).attr('id') + ' .tipo_grafico_principal').val();
                datos.orden = $(this).attr('orden');
                datos.posicion = posicion;
                arreglo_indicadores[i] = datos;
                i++;
            }
            posicion++;

        });
        datos_sala.nombre = $('#nombre_sala').val();
        datos_sala.id = $('#nombre_sala').attr('id-sala');
        datos_sala.datos_indicadores = arreglo_indicadores;

        $.getJSON(Routing.generate('sala_guardar'), {datos: JSON.stringify(datos_sala)},
        function(resp) {
            if (resp.estado === 'ok') {
                $('#nombre_sala').attr('id-sala', resp.id_sala);
                $('#header_sala').html('<span class="glyphicon glyphicon-th"></span> ' + $('#nombre_sala').val() );
                $('#myModal').modal('toggle');
            }
            else {
                $('#info_sala').html('_error_guardar_sala_').addClass('alert-danger');
            }

        });
    });       
});
function marcar_agregados()
{
	var valor=" ";
	$('.area_grafico').each(function() 
	{
		valor=valor+$(this).find('.titulo_indicador').attr("data-id")+"-"; 
	});
	if ( valor!=' ') 
	{				
		long=$("#miclasificacion").children("li").length;
		
		for(var a=1;a<=long;a++) 
		{
			var mid=$.trim($("#miclasificacion li:nth-child("+a+")").find('button').attr("data-id"));				
			if(valor.search(mid)>0)
			{
				$("#"+mid).addClass("active btn-success");
				$("#"+mid).html('<i class="glyphicon glyphicon-ok"></i>');
				
				$("#fav-"+mid).addClass("active btn-success");
				$("#fav-"+mid).html('<i class="glyphicon glyphicon-ok"></i>');
			}
			else
			{
				$("#"+mid).removeClass("active");
				$("#"+mid).removeClass("btn-success");			
				$("#"+mid).html('<i class="glyphicon glyphicon-plus"></i>');
				
				$("#fav-"+mid).removeClass("active");
				$("#fav-"+mid).removeClass("btn-success");			
				$("#fav-"+mid).html('<i class="glyphicon glyphicon-plus"></i>');
			}
		}
	}
}

function sala_agregar_fila() 
{
	$('div .area_grafico').removeClass('zona_actual');
	var cant = 1  +  Math . floor ( Math . random ()  *  999999999 );
	var html =  '<div class="col-md-4">'+
					'<div class="panel panel-default area_grafico zona_actual" data-id="'+parseInt(cant+1)+'" id="grafico_' + parseInt(cant+1) + '" data-dimension="" data-desde="" data-hasta="" data-elementos="" data-codigo="" data-valor="" data-filtroActivo="" data-graficos="">' +						
					'<div class="panel-heading">'+
					'<strong>'+
						'<div class="titulo"><span class="titulo_indicador"></span>'+
						'<span>('+trans.por+' <span class="dimension" ></span>)</span></div>'+
					'</strong>'+
					'</div>'+
					'<div class="panel-body">'+	
						'<div class="controles btn-toolbar"></div>' +
						'<ol class="filtros_dimensiones breadcrumb" style="margin-top:10px; display:none;"></ol>' +		
						'<div class="info" style="display:none;" ></div>' +
						'<div class="row_grafico" style="margin-top:10px">' +
							'<div class="grafico" ></div>' +
						'</div>' +  
					'</div>'+
					'<div class="panel-footer"></div>'+
				'</div></div>';         
	var contador_indicadores = 0;
	$('#sala .row').last().find('.col-md-4').each(function(){
		contador_indicadores++;
	});
	if(contador_indicadores==0||contador_indicadores==3)
	{
		row="<div class='row'>"+html+"</div>";
		$('#sala').append(row); 
	}
	else if(contador_indicadores<=2)
		$('#sala .row').last().append(html); 
		 
		
	$('DIV.area_grafico').on('click',function(e) {
		zona_elegir(this);
	});
}

function sala_quitar_fila() 
{
	var cant = $('DIV.area_grafico').length;
	if(parseInt(cant)>1)
	$('#grafico_' + parseInt(cant)).remove();                
}
function dibujarIndicador(id_indicador) 
{
	recuperarDimensiones(id_indicador, null);
}
function ver_ficha_tecnica(id_indicador) 
{
	$.get(Routing.generate('get_indicador_ficha', {id: id_indicador}));
}
function zona_elegir(zona) 
{
	$('div .area_grafico').removeClass('zona_actual');
	$(zona).addClass('zona_actual');
}
function cargar_indicador(mid)
{
	if($("#"+mid).hasClass("active"))
	{						
		id=$("span[data-id='"+$("#"+mid).attr('data-id')+"']").parent('div').parent('strong').parent('div').parent('div').attr('id');
		
		$(".zona_actual").removeClass("zona_actual");
		$("#"+id).addClass("zona_actual");
		limpiarZona2(id);			
		
	}
	else
	{
		$("#_guardar_sala_").attr("style","display:");
		sala_agregar_fila(); 
		
		$("#"+mid).addClass("active btn-success");
		$("#"+mid).html('<i class="glyphicon glyphicon-ok"></i>');
		
		$("#fav-"+mid).addClass("active btn-success");
		$("#fav-"+mid).html('<i class="glyphicon glyphicon-ok"></i>');
		
		
		dibujarIndicador($("#"+mid).attr('data-id'));
	}
}