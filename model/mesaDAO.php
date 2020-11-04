<?php
require_once 'mesa.php';
class MesaDAO {
    private $pdo;

    public function __construct(){
        include '../db/connection.php';
        $this->pdo=$pdo;
    }

    public function getPDO() {
        return $this->pdo;
    }

    public function getMesas() {
        $nombre_camarero = $_SESSION['camarero']->getNombre_camarero();
        $con = 0;

        if(isset($_REQUEST['espacio'])){
            $tipoEspacio=$_REQUEST['espacio'];
        } else {
            $tipoEspacio="Libre";
        }

        $query = "SELECT * FROM mesas INNER JOIN espacio ON mesas.id_espacio = espacio.id_espacio LEFT JOIN camareros
        ON mesas.id_camarero = camareros.id_camarero WHERE tipo_espacio = ?;";
        $sentencia = $this->pdo->prepare($query);
        $sentencia->bindParam(1, $tipoEspacio);
        $sentencia->execute();
        $lista_mesas = $sentencia->fetchAll(PDO::FETCH_ASSOC);

        foreach($lista_mesas as $mesa) {
            // COMPROBAMOS EL ESTADO DE LA MESA
            $idMesa = $mesa['id_mesa'];
            $estado = $mesa['disp_mesa'];
            if($con%4==0){
                echo "<tr>";
            }
            $con++;
            // IMPRIMIMOS LAS MESAS SEGUN SU ESTADO
            if($estado == "Libre") {
                echo "<td>";
                echo "<p class='pHistorico'><a class='aHistorico' href='./regMesaHorarios.php'><img src='../img/history.png' alt='historial'></a></p>";
                echo "<a href='../view/editMesa.php?id_mesa={$idMesa}'><img src='../img/mesa.png'></img></a>";
                echo "<p>Nº mesa: $idMesa</p>";
                echo "<p>Camarero asignado: {$mesa['nombre_camarero']}</p>";
                echo "<p>Comensal/es: 0</p>";
                echo "<p>Libre</p>";
                echo "<p>Capacidad máxima: {$mesa['capacidad_max']} personas</p>";
                echo "</td>";
            } else if ($estado == "Ocupada") {
                echo "<td>";
                echo "<p class='pHistorico'><a class='aHistorico' href='./regMesaHorarios.php'><img src='../img/history.png' alt='historial'></a></p>";
                echo "<a href='../view/editMesa.php?id_mesa={$idMesa}'><img src='../img/mesaOcupada.png'></img></a>";
                echo "<p>Nº mesa: $idMesa</p>";
                echo "<p>Camarero asignado: {$mesa['nombre_camarero']}</p>";
                echo "<p>Comensal/es: {$mesa['capacidad_mesa']}</p>";
                echo "<p>Ocupada</p>";
                echo "<p>Capacidad máxima: {$mesa['capacidad_max']} personas</p>";
                echo "</td>";
            } else {
                echo "<td>";
                echo "<p class='pHistorico'><a class='aHistorico' href='./regMesaHorarios.php'><img src='../img/history.png' alt='historial'></a></p>";
                echo "<a href='../view/editMesa.php?id_mesa={$idMesa}'><img src='../img/mesaReparacion.png'></img></a>";
                echo "<p>Nº mesa: $idMesa</p>";
                echo "<p>Capacidad máxima: {$mesa['capacidad_max']} personas</p>";
                echo "</td>";
            }
            if($con%4==0){
                echo "</tr>";
            }
        }
    }

    public function updateEntrada() {
        try {
            include '../controller/sessionController.php';
            include './camarero.php';
            $this->pdo->beginTransaction();
            $id_camarero = $_SESSION['camarero']->getId_camarero();
            $id_mesa = $_REQUEST['id_mesa'];
            $disp_mesa = $_REQUEST['disp_mesa'];
            $capacidad_mesa = $_REQUEST['capacidad_mesa'];
            $espacio = $_REQUEST['tipo_espacio'];

            $url = "../view/zonaRestaurante.php?espacio={$espacio}";

            $query="UPDATE mesas SET mesas.capacidad_mesa = ?, mesas.id_camarero = ?, mesas.disp_mesa = ? WHERE id_mesa = ?;";
            $sentencia=$this->pdo->prepare($query);
            $sentencia->bindParam(1,$capacidad_mesa);
            $sentencia->bindParam(2,$id_camarero);
            $sentencia->bindParam(3,$disp_mesa);
            $sentencia->bindParam(4,$id_mesa);
            $sentencia->execute();

            $query = "INSERT INTO horario (hora_entrada, id_mesa) VALUES (NOW(), ?);";
            $sentencia=$this->pdo->prepare($query);
            $sentencia->bindParam(1,$id_mesa);
            echo $query;
            $sentencia->execute();
            
            $this->pdo->commit();
            header('Location: '.$url);
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            echo $e;
        }
    }
    
    public function updateSalida() {
        try {
            $this->pdo->beginTransaction();
            $id_mesa = $_REQUEST['id_mesa'];
            $disp_mesa = $_REQUEST['disp_mesa'];
            $capacidad_mesa = $_REQUEST['capacidad_mesa'];
            $espacio = $_REQUEST['tipo_espacio'];
            
            $url = "../view/zonaRestaurante.php?espacio={$espacio}";
            
            $query="UPDATE mesas SET mesas.capacidad_mesa = ?, mesas.id_camarero = NULL, mesas.disp_mesa = ? WHERE id_mesa = ?;";
            $sentencia=$this->pdo->prepare($query);
            $sentencia->bindParam(1,$capacidad_mesa);
            $sentencia->bindParam(2,$disp_mesa);
            $sentencia->bindParam(3,$id_mesa);
            $sentencia->execute();
            
            $query = "UPDATE horario SET hora_salida = NOW() WHERE id_mesa = ? AND hora_entrada = (SELECT MAX(hora_entrada) FROM horario)";
            $sentencia=$this->pdo->prepare($query);
            $sentencia->bindParam(1,$id_mesa);
            $sentencia->execute();

            $this->pdo->commit();
            header('Location: '.$url);
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            echo $e;
        }
    }
    
    public function viewMesas() {
        try {
            $cont = 0;
            $query = "SELECT id_mesa FROM mesas";
            $sentencia=$this->pdo->prepare($query);
            $sentencia->execute();
            $lista_mesas = $sentencia->fetchAll(PDO::FETCH_ASSOC);
            foreach ($lista_mesas as $mesa) {
                if ($cont%10==0) {
                    echo "<tr>";
                }
                    $cont++;
                    echo "<td><a href='./regMesa.php?id_mesa={$mesa['id_mesa']}'>Mesa Nº ".$mesa['id_mesa']."</a></td>";
                if ($cont%10==0) {
                    echo "</tr>";
                }
            }

        } catch (Exception $e) {

            echo $e;
        
        }
    }

    public function viewHistorical() {
        try {
            $this->pdo->beginTransaction();
            $id_mesa = $_REQUEST['id_mesa'];

            $query = "SELECT hora_entrada, hora_salida FROM horario WHERE id_mesa = ?";
            $sentencia=$this->pdo->prepare($query);
            $sentencia->bindParam(1,$id_mesa);
            $sentencia->execute();
            $lista_horas = $sentencia->fetchAll(PDO::FETCH_ASSOC);

            foreach ($lista_horas as $hora) {
                    echo "<tr>";
                    echo "<td>Hora entrada: {$hora['hora_entrada']}</td>";
                    echo "<td>Hora salida: {$hora['hora_salida']}</td>";
                    echo "</tr>";
            }

            $this->pdo->commit();
        } catch (Exception $e) {
            $this->pdo->rollBack();
            echo $e;
        }
    } 
}

?>