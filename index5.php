<?php
// Conectar a la base de datos
$conexion = new mysqli("localhost", "root", "root", "sakila");
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

// Consulta para obtener datos de los filmes y sus actores
$query = "
    SELECT 
        f.film_id AS codigo_filme,
        f.title AS nombre_filme,
        f.description AS descripcion,
        GROUP_CONCAT(DISTINCT c.name SEPARATOR ', ') AS categorias,
        f.release_year AS anio,
        CONCAT(a.first_name, ' ', a.last_name) AS nombre_actor
    FROM film f
    INNER JOIN film_category fc ON f.film_id = fc.film_id
    INNER JOIN category c ON fc.category_id = c.category_id
    INNER JOIN film_actor fa ON f.film_id = fa.film_id
    INNER JOIN actor a ON fa.actor_id = a.actor_id
    GROUP BY f.film_id, f.title, f.description, f.release_year
    ORDER BY f.title, nombre_actor;
";

$result = $conexion->query($query); 

// Crear un objeto XML
$xml = new DOMDocument('1.0', 'UTF-8');
$xml->formatOutput = true;

// Crear el nodo raíz
$root = $xml->createElement('FilmesConElenco');
$xml->appendChild($root);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        // Crear el nodo para cada filme
        $filme = $xml->createElement('Filme');

        // Crear nodos para los datos del filme
        $codigo = $xml->createElement('Codigo', $row['codigo_filme']);
        $nombre = $xml->createElement('Nombre', $row['nombre_filme']);
        $descripcion = $xml->createElement('Descripcion', $row['descripcion']);
        $anio = $xml->createElement('Anio', $row['anio']);
        $categorias = $xml->createElement('Categorias', $row['categorias']);
        
        // Añadir los nodos al filme
        $filme->appendChild($codigo);
        $filme->appendChild($nombre);
        $filme->appendChild($descripcion);
        $filme->appendChild($anio);
        $filme->appendChild($categorias);

        // Crear el nodo de actores
        $actores = $xml->createElement('ListaActores');
        $nombre_actor = $xml->createElement('Actor', $row['nombre_actor']);
        $actores->appendChild($nombre_actor);

        // Añadir los actores al filme
        $filme->appendChild($actores);

        // Añadir el filme al nodo raíz
        $root->appendChild($filme);
    }
}

// Guardar el archivo XML
$xml->save('filmes_con_elenco.xml');

// Cerrar la conexión a la base de datos
$conexion->close(); 

// Cargar el archivo XML para mostrar en la tabla
$xml = simplexml_load_file('filmes_con_elenco.xml');

if ($xml === false) {
    echo "Error cargando el archivo XML.";
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Filmes</title>
    <link rel="stylesheet" type="text/css" href="estilos/styles01.css"> <!-- Cambia el path si es necesario -->
</head>
<body>
    <h1>Reporte de Filmes y Actores</h1>

    <table>
        <thead>
            <tr>
                <th>Código</th>
                <th>Nombre</th>
                <th>Descripción</th>
                <th>Año</th>
                <th>Categorías</th>
                <th>Actores</th>
            </tr>
        </thead>
        <tbody>
            <?php
            foreach ($xml->Filme as $filme) {
                echo "<tr>";
                echo "<td>{$filme->Codigo}</td>";
                echo "<td>{$filme->Nombre}</td>";
                echo "<td>{$filme->Descripcion}</td>";
                echo "<td>{$filme->Anio}</td>";
                echo "<td>{$filme->Categorias}</td>";
                echo "<td>";
                foreach ($filme->ListaActores->Actor as $actor) {
                    echo "{$actor} <br>"; // Mostrar múltiples actores si hay
                }
                echo "</td>";
                echo "</tr>";
            }
            ?>
        </tbody>
    </table>
</body>
</html>
