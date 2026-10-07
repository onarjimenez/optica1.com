<?php
https://htcvluwukqlensyqzyxu.supabase.co 
ini_set('display_errors','1'); error_reporting(E_ALL);
// Sistema de Gestión – Óptica (MVP Fase 1). Datos en datos_optica.json (no requiere base de datos).
session_start();
const DB = __DIR__ . '/datos_optica.json';
const ESTADOS = ['Orden creada','Enviada al laboratorio','En producción','Recibida','Control de calidad','Lista para entrega','Entregada'];
const PERM = [
  'administrador' => ['dashboard','clientes','productos','inventario','ventas','ordenes','usuarios'],
  'optometra'     => ['dashboard','clientes'],
  'vendedor'      => ['dashboard','clientes','ventas'],
  'auxiliar'      => ['dashboard','productos','inventario','ventas','ordenes'],
];
$S = [
  'clientes' => ['t'=>'Clientes','cols'=>['documento','nombre','telefono','correo','fecha_registro'],'f'=>[
    ['documento','Documento de identidad','text'],['nombre','Nombre completo','text'],['fecha_nac','Fecha de nacimiento','date'],
    ['telefono','Teléfono','text'],['correo','Correo','email'],['direccion','Dirección','text'],['observaciones','Observaciones','textarea']]],
  'productos' => ['t'=>'Catálogo de productos','cols'=>['codigo','categoria','marca','modelo','proveedor','precio','stock'],'f'=>[
    ['codigo','Código','text'],['categoria','Categoría','select',['Montura','Lente','Accesorio']],['marca','Marca','text'],['modelo','Modelo','text'],
    ['descripcion','Descripción','text'],['proveedor','Proveedor','text'],['precio','Precio','number'],['stock','Existencias','number']]],
  'usuarios' => ['t'=>'Usuarios y roles','cols'=>['nombre','correo','rol'],'f'=>[
    ['nombre','Nombre','text'],['correo','Correo','email'],['rol','Rol','select',array_keys(PERM)],['clave','Contraseña','password']]],
];

function seed() {
  $u = function($i,$n,$c,$r,$p){ return ['id'=>$i,'nombre'=>$n,'correo'=>$c,'rol'=>$r,'clave'=>password_hash($p,PASSWORD_DEFAULT)]; };
  $p = function($i,$c,$cat,$ma,$mo,$pr,$prc,$s){ return ['id'=>$i,'codigo'=>$c,'categoria'=>$cat,'marca'=>$ma,'modelo'=>$mo,'descripcion'=>'','proveedor'=>$pr,'precio'=>$prc,'stock'=>$s]; };
  return [
    'seq'=>['usuarios'=>4,'clientes'=>3,'productos'=>6,'ventas'=>0,'ordenes'=>0,'movimientos'=>0],
    'usuarios'=>[$u(1,'Administrador','admin@optica.com','administrador','admin123'),$u(2,'Optómetra','optometra@optica.com','optometra','opto123'),
                 $u(3,'Vendedor','vendedor@optica.com','vendedor','vende123'),$u(4,'Auxiliar','auxiliar@optica.com','auxiliar','aux123')],
    'clientes'=>[
      ['id'=>1,'documento'=>'1020304050','nombre'=>'María Fernanda Ruiz','fecha_nac'=>'1990-05-14','telefono'=>'3001112233','correo'=>'maria@correo.com','direccion'=>'Calle 10 # 5-20','observaciones'=>'','fecha_registro'=>date('Y-m-d')],
      ['id'=>2,'documento'=>'1098765432','nombre'=>'Carlos Pérez','fecha_nac'=>'1985-11-02','telefono'=>'3109998877','correo'=>'carlos@correo.com','direccion'=>'Carrera 3 # 12-45','observaciones'=>'Usa lentes progresivos','fecha_registro'=>date('Y-m-d')],
      ['id'=>3,'documento'=>'52123456','nombre'=>'Laura Gómez','fecha_nac'=>'2001-02-28','telefono'=>'3205554433','correo'=>'laura@correo.com','direccion'=>'Av. Principal 8-10','observaciones'=>'','fecha_registro'=>date('Y-m-d')]],
    'productos'=>[$p(1,'MON-001','Montura','Ray-Ban','RB5154','Distribuidora Visión',320000,8),$p(2,'MON-002','Montura','Oakley','OX8046','Distribuidora Visión',410000,2),
                  $p(3,'LEN-001','Lente','Essilor','Monofocal CR-39','Laboratorio Óptico SA',120000,20),$p(4,'LEN-002','Lente','Essilor','Progresivo Varilux','Laboratorio Óptico SA',480000,6),
                  $p(5,'ACC-001','Accesorio','Genérico','Estuche rígido','Insumos Ópticos',15000,30),$p(6,'ACC-002','Accesorio','Genérico','Líquido limpiador','Insumos Ópticos',12000,0)],
    'ventas'=>[],'ordenes'=>[],'movimientos'=>[],
  ];
}
$D = file_exists(DB) ? json_decode(file_get_contents(DB), true) : seed();
if (!file_exists(DB)) file_put_contents(DB, json_encode($D, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));

function save(){ global $D; file_put_contents(DB, json_encode($D, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE), LOCK_EX); }
function nid($t){ global $D; return $D['seq'][$t] = ($D['seq'][$t] ?? 0) + 1; }
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function find($t,$id){ global $D; foreach ($D[$t] as $i=>$r) if ($r['id'] == $id) return $i; return null; }
function money($n){ return '$' . number_format((float)$n, 0, ',', '.'); }
function flash($t){ $_SESSION['msg'] = $t; }
function go($m){ header('Location: index.php?m=' . $m); exit; }
function nom($t,$id,$k='nombre'){ $i = find($t,$id); global $D; return $i === null ? '—' : $D[$t][$i][$k]; }
function mov($pid,$t,$c,$nota){ global $D,$u; $D['movimientos'][] = ['id'=>nid('movimientos'),'fecha'=>date('Y-m-d H:i'),'producto_id'=>$pid,'tipo'=>$t,'cantidad'=>$c,'nota'=>$nota,'usuario'=>$u['nombre']]; }

$u = $_SESSION['u'] ?? null;
$m = $_GET['m'] ?? 'dashboard';
if (isset($_GET['salir'])) { session_destroy(); header('Location: index.php'); exit; }

// ---------- LOGIN ----------
if (!$u) {
  $err = '';
  if (isset($_POST['login'])) {
    foreach ($D['usuarios'] as $x) if (strtolower($x['correo']) === strtolower(trim($_POST['correo'])) && $x['rol'] === ($_POST['rol'] ?? '') && password_verify($_POST['clave'], $x['clave'])) {
      $_SESSION['u'] = ['id'=>$x['id'],'nombre'=>$x['nombre'],'rol'=>$x['rol']]; header('Location: index.php'); exit;
    }
    $err = 'Correo, contraseña o rol incorrectos.';
  }
  echo '<!DOCTYPE html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Óptica – Ingreso</title><link rel="stylesheet" href="style.css"></head><body class="login"><form method="post" class="card"><h1>👓 Gestión de Óptica</h1>';
  if ($err) echo '<div class="msg err">' . h($err) . '</div>';
  echo '<label>Rol<select name="rol" required><option value="administrador">Administrador</option><option value="optometra">Optómetra</option><option value="vendedor">Vendedor</option><option value="auxiliar">Auxiliar</option></select></label><label>Correo<input type="email" name="correo" required></label><label>Contraseña<input type="password" name="clave" required></label><button name="login" value="1">Iniciar sesión</button>
  <p class="hint">Demo: admin@optica.com / admin123<br>vendedor@optica.com / vende123<br>auxiliar@optica.com / aux123<br>optometra@optica.com / opto123</p></form></body></html>';
  exit;
}
if (!in_array($m, PERM[$u['rol']])) $m = 'dashboard';

// ---------- ACCIONES (POST) ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($S[$m]) && isset($_POST['guardar'])) {
    $r = [];
    foreach ($S[$m]['f'] as $f) { $v = trim($_POST[$f[0]] ?? ''); $r[$f[0]] = $f[2]==='number' ? (float)$v : $v; }
    $i = find($m, $_POST['id'] ?? 0);
    if ($m === 'usuarios') $r['clave'] = $r['clave'] !== '' ? password_hash($r['clave'], PASSWORD_DEFAULT) : ($i !== null ? $D[$m][$i]['clave'] : '');
    if ($i !== null) $D[$m][$i] = array_merge($D[$m][$i], $r);
    else { $r['id'] = nid($m); if ($m === 'clientes') $r['fecha_registro'] = date('Y-m-d'); $D[$m][] = $r; }
    save(); flash('Registro guardado correctamente.'); go($m);
  }
  if (isset($S[$m]) && isset($_POST['del']) && $u['rol'] === 'administrador') {
    $i = find($m, $_POST['del']);
    if ($m === 'usuarios' && $_POST['del'] == $u['id']) flash('⚠ No puedes eliminar tu propio usuario.');
    elseif ($i !== null) { array_splice($D[$m], $i, 1); save(); flash('Registro eliminado.'); }
    go($m);
  }
  if ($m === 'inventario' && isset($_POST['mov'])) {
    $i = find('productos', $_POST['producto_id'] ?? 0); $c = (int)($_POST['cantidad'] ?? -1); $t = $_POST['tipo'] ?? '';
    if ($i === null || $c < 0) flash('⚠ Datos del movimiento no válidos.');
    elseif ($t === 'Salida' && $c > $D['productos'][$i]['stock']) flash('⚠ La salida supera las existencias.');
    else {
      $s = &$D['productos'][$i]['stock'];
      if ($t === 'Entrada') $s += $c; elseif ($t === 'Salida') $s -= $c; else $s = $c;
      unset($s); mov($D['productos'][$i]['id'], $t, $c, trim($_POST['nota'] ?? '')); save(); flash('Movimiento registrado.');
    }
    go('inventario');
  }
  if ($m === 'ventas' && isset($_POST['vender'])) {
    $L = []; $tot = 0; $err = '';
    foreach ($_POST['p'] ?? [] as $k => $pid) {
      $q = (int)($_POST['q'][$k] ?? 0); $i = find('productos', $pid);
      if ($i === null || $q < 1) continue;
      $pr = $D['productos'][$i];
      if ($pr['stock'] < $q) { $err = 'Existencias insuficientes de ' . $pr['codigo']; break; }
      $L[] = ['pid'=>$pr['id'],'q'=>$q,'pu'=>$pr['precio']]; $tot += $q * $pr['precio'];
    }
    $serv = (float)($_POST['servicios'] ?? 0); $tot += $serv;
    if (!$err && find('clientes', $_POST['cliente_id'] ?? 0) === null) $err = 'Selecciona un cliente.';
    if (!$err && !$L) $err = 'Agrega al menos un producto.';
    if ($err) { flash('⚠ ' . $err); go('ventas'); }
    $vid = nid('ventas');
    foreach ($L as $l) { $i = find('productos', $l['pid']); $D['productos'][$i]['stock'] -= $l['q']; mov($l['pid'], 'Salida', $l['q'], 'Venta #' . $vid); }
    $D['ventas'][] = ['id'=>$vid,'fecha'=>date('Y-m-d H:i'),'cliente_id'=>(int)$_POST['cliente_id'],'items'=>$L,'servicios'=>$serv,'total'=>$tot,'pago'=>$_POST['pago'],'usuario'=>$u['nombre']];
    if (!empty($_POST['crear_orden']))
      $D['ordenes'][] = ['id'=>nid('ordenes'),'venta_id'=>$vid,'cliente_id'=>(int)$_POST['cliente_id'],'estado'=>ESTADOS[0],'seg'=>[[ESTADOS[0], date('Y-m-d H:i'), $u['nombre']]]];
    save(); flash('Venta #' . $vid . ' registrada por ' . money($tot) . '.'); go('ventas');
  }
  if ($m === 'ordenes' && isset($_POST['avanzar'])) {
    $i = find('ordenes', $_POST['avanzar']);
    if ($i !== null) {
      $o = &$D['ordenes'][$i]; $k = array_search($o['estado'], ESTADOS);
      if ($k !== false && $k < count(ESTADOS) - 1) { $o['estado'] = ESTADOS[$k+1]; $o['seg'][] = [$o['estado'], date('Y-m-d H:i'), $u['nombre']]; }
      unset($o); save();
    }
    go('ordenes');
  }
}

// ---------- VISTAS ----------
function crud($m) {
  global $D,$S,$u; $e = $S[$m]; $edit = null;
  if (isset($_GET['edit'])) { $i = find($m, $_GET['edit']); if ($i !== null) $edit = $D[$m][$i]; }
  echo '<h2>' . $e['t'] . '</h2><form method="post" class="card grid"><input type="hidden" name="id" value="' . h($edit['id'] ?? '') . '">';
  foreach ($e['f'] as $k => $f) {
    list($n,$l,$t) = $f; $v = $t === 'password' ? '' : ($edit[$n] ?? '');
    echo '<label>' . $l;
    if ($t === 'textarea') echo '<textarea name="' . $n . '">' . h($v) . '</textarea>';
    elseif ($t === 'select') { echo '<select name="' . $n . '">'; foreach ($f[3] as $o) echo '<option' . ($o == $v ? ' selected' : '') . '>' . h($o) . '</option>'; echo '</select>'; }
    else echo '<input type="' . $t . '" name="' . $n . '" value="' . h($v) . '"' . ($t==='number' ? ' min="0" step="any"' : '') . ((($k < 2 && $t !== 'password') || ($t === 'password' && !$edit)) ? ' required' : '') . '>';
    echo '</label>';
  }
  echo '<div><button name="guardar" value="1">' . ($edit ? 'Actualizar' : 'Guardar') . '</button>' . ($edit ? ' <a class="btn2" href="?m=' . $m . '">Cancelar</a>' : '') . '</div></form>';
  $q = trim($_GET['q'] ?? '');
  echo '<form class="search"><input type="hidden" name="m" value="' . $m . '"><input name="q" placeholder="Buscar…" value="' . h($q) . '"><button>Buscar</button></form>';
  $lab = []; foreach ($e['f'] as $f) $lab[$f[0]] = $f[1]; $lab['fecha_registro'] = 'Registro';
  echo '<table><tr>'; foreach ($e['cols'] as $c) echo '<th>' . $lab[$c] . '</th>'; echo '<th></th></tr>';
  foreach ($D[$m] as $r) {
    if ($q !== '' && stripos(json_encode($r, JSON_UNESCAPED_UNICODE), $q) === false) continue;
    echo '<tr>';
    foreach ($e['cols'] as $c) {
      $v = $r[$c] ?? '';
      if ($c === 'precio') $v = money($v);
      echo '<td>' . ($c === 'stock' ? '<span class="b ' . ($v <= 0 ? 'rojo' : ($v <= 3 ? 'amar' : 'verde')) . '">' . h($v) . '</span>' : h($v)) . '</td>';
    }
    echo '<td class="acc"><a class="btn2" href="?m=' . $m . '&edit=' . $r['id'] . '">Editar</a>';
    if ($u['rol'] === 'administrador') echo '<form method="post"><input type="hidden" name="del" value="' . $r['id'] . '"><button class="del" onclick="return confirm(\'¿Eliminar este registro?\')">Eliminar</button></form>';
    echo '</td></tr>';
  }
  echo '</table>';
}

function prodOpts() { global $D; $o = '<option value="">— Producto —</option>'; foreach ($D['productos'] as $p) $o .= '<option value="' . $p['id'] . '">' . h($p['codigo'] . ' · ' . $p['marca'] . ' ' . $p['modelo'] . ' (' . $p['stock'] . ') ' . money($p['precio'])) . '</option>'; return $o; }

$nav = ['dashboard'=>'Dashboard','clientes'=>'Clientes','productos'=>'Productos','inventario'=>'Inventario','ventas'=>'Ventas','ordenes'=>'Órdenes','usuarios'=>'Usuarios'];
echo '<!DOCTYPE html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Óptica – Gestión</title><link rel="stylesheet" href="style.css"></head><body><header><b>👓 Óptica</b><nav>';
foreach ($nav as $k => $v) if (in_array($k, PERM[$u['rol']])) echo '<a href="?m=' . $k . '"' . ($k === $m ? ' class="on"' : '') . '>' . $v . '</a>';
echo '</nav><span>' . h($u['nombre']) . ' (' . h($u['rol']) . ') · <a href="?salir=1">Salir</a></span></header><main>';
if (!empty($_SESSION['msg'])) { echo '<div class="msg">' . h($_SESSION['msg']) . '</div>'; unset($_SESSION['msg']); }

if (isset($S[$m])) crud($m);
elseif ($m === 'dashboard') {
  $bajo = count(array_filter($D['productos'], function($p){ return $p['stock'] <= 3; }));
  $pend = count(array_filter($D['ordenes'], function($o){ return $o['estado'] !== 'Entregada'; }));
  echo '<h2>Dashboard</h2><div class="cards">';
  foreach ([['Clientes', count($D['clientes'])], ['Ventas', count($D['ventas'])], ['Total vendido', money(array_sum(array_column($D['ventas'], 'total')))], ['Órdenes pendientes', $pend], ['Productos con bajo stock', $bajo]] as $c)
    echo '<div class="card kpi"><span>' . $c[0] . '</span><b>' . $c[1] . '</b></div>';
  echo '</div><h3>Órdenes por estado</h3><table><tr><th>Estado</th><th>Cantidad</th></tr>';
  foreach (ESTADOS as $e) echo '<tr><td>' . $e . '</td><td>' . count(array_filter($D['ordenes'], function($o) use ($e){ return $o['estado'] === $e; })) . '</td></tr>';
  echo '</table>';
}
elseif ($m === 'inventario') {
  echo '<h2>Inventario</h2><form method="post" class="card grid"><label>Producto<select name="producto_id">' . prodOpts() . '</select></label>
  <label>Tipo<select name="tipo"><option>Entrada</option><option>Salida</option><option>Ajuste</option></select></label>
  <label>Cantidad (en Ajuste: nueva existencia)<input type="number" name="cantidad" min="0" required></label><label>Nota<input name="nota"></label>
  <div><button name="mov" value="1">Registrar movimiento</button></div></form>
  <table><tr><th>Código</th><th>Producto</th><th>Existencias</th><th>Estado</th></tr>';
  foreach ($D['productos'] as $p) { $s = $p['stock']; echo '<tr><td>' . h($p['codigo']) . '</td><td>' . h($p['marca'] . ' ' . $p['modelo']) . '</td><td>' . $s . '</td><td><span class="b ' . ($s <= 0 ? 'rojo">Agotado' : ($s <= 3 ? 'amar">Bajo inventario' : 'verde">Disponible')) . '</span></td></tr>'; }
  echo '</table><h3>Últimos movimientos</h3><table><tr><th>Fecha</th><th>Producto</th><th>Tipo</th><th>Cant.</th><th>Nota</th><th>Usuario</th></tr>';
  foreach (array_slice(array_reverse($D['movimientos']), 0, 15) as $x) echo '<tr><td>' . $x['fecha'] . '</td><td>' . h(nom('productos', $x['producto_id'], 'codigo')) . '</td><td>' . $x['tipo'] . '</td><td>' . $x['cantidad'] . '</td><td>' . h($x['nota']) . '</td><td>' . h($x['usuario']) . '</td></tr>';
  echo '</table>';
}
elseif ($m === 'ventas') {
  echo '<h2>Ventas</h2><form method="post" class="card grid"><label>Cliente<select name="cliente_id"><option value="">— Cliente —</option>';
  foreach ($D['clientes'] as $c) echo '<option value="' . $c['id'] . '">' . h($c['nombre']) . '</option>';
  echo '</select></label>';
  for ($k = 0; $k < 3; $k++) echo '<label>Producto ' . ($k+1) . '<select name="p[]">' . prodOpts() . '</select></label><label>Cantidad<input type="number" name="q[]" min="1" value="1"></label>';
  echo '<label>Servicios adicionales ($)<input type="number" name="servicios" min="0" value="0"></label>
  <label>Forma de pago<select name="pago"><option>Efectivo</option><option>Tarjeta</option><option>Transferencia</option></select></label>
  <label class="chk"><input type="checkbox" name="crear_orden" value="1" checked> Crear orden de lentes</label>
  <div><button name="vender" value="1">Registrar venta</button></div></form>
  <table><tr><th>#</th><th>Fecha</th><th>Cliente</th><th>Artículos</th><th>Pago</th><th>Total</th><th>Atendió</th></tr>';
  foreach (array_reverse($D['ventas']) as $v) echo '<tr><td>' . $v['id'] . '</td><td>' . $v['fecha'] . '</td><td>' . h(nom('clientes', $v['cliente_id'])) . '</td><td>' . array_sum(array_column($v['items'], 'q')) . '</td><td>' . h($v['pago']) . '</td><td>' . money($v['total']) . '</td><td>' . h($v['usuario']) . '</td></tr>';
  echo '</table>';
}
elseif ($m === 'ordenes') {
  echo '<h2>Órdenes de lentes</h2><table><tr><th>#</th><th>Venta</th><th>Cliente</th><th>Estado</th><th>Seguimiento</th><th></th></tr>';
  foreach (array_reverse($D['ordenes']) as $o) {
    echo '<tr><td>' . $o['id'] . '</td><td>#' . $o['venta_id'] . '</td><td>' . h(nom('clientes', $o['cliente_id'])) . '</td><td><span class="b ' . ($o['estado'] === 'Entregada' ? 'verde' : 'azul') . '">' . $o['estado'] . '</span></td><td><details><summary>Ver</summary>';
    foreach ($o['seg'] as $s) echo '<small>' . h($s[1] . ' · ' . $s[0] . ' · ' . $s[2]) . '</small><br>';
    echo '</details></td><td>';
    if ($o['estado'] !== 'Entregada') echo '<form method="post"><input type="hidden" name="avanzar" value="' . $o['id'] . '"><button>Avanzar →</button></form>';
    echo '</td></tr>';
  }
  echo '</table>';
}
echo '</main></body></html>';