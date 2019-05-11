<?php
// Test this using following command
// php -S localhost:8080 ./index.php &
// curl http://localhost:8080 -d '{"query": "query { authUser(usuario: \"Fofo\", contrasena: \"123\") }" }' &
// curl http://localhost:8080 -d '{"query": "query { postUser(usuario: \"Fofo\", contrasena: \"123\") }" }' &
// curl http://localhost:8080 -d '{"query": "query { updateUser(usuario: \"Fofo\", contrasena: \"123\") }" }'

// Project requirements
require_once __DIR__ . '/vendor/autoload.php';

// Use dependencies  of Grap
include_once 'src/BeforeValidException.php';
include_once 'src/ExpiredException.php';
include_once 'src/SignatureInvalidException.php';
include_once 'src/JWT.php';

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;
use GraphQL\GraphQL;
use \Firebase\JWT\JWT;

// Connect to mysql
$conn = new mysqli("192.168.100.52", "ijtj03", "1234", "comprassoa", 3306);

// Database connection verification
if($conn->connect_errno) {
    error_log("Failed to connect to MySQL: (" . $conn->connect_errno . ") " . $conn->connect_error);
}

try {
    // Define queryType ObjectType
    $queryType = new ObjectType([
        'name' => 'Query',
        'fields' => [
            'autenticar' => [
                'type' => Type::string(),
                'args' => [
                    'usuario' => ['type' => Type::string()],
                    'contrasena' => ['type' => Type::string()],
                ],
                'resolve' => function($root, $args) {
                    $usuario = $args['usuario'];
                    $contrasena = $args['contrasena'];
                    global $conn;

                    $sql = "SELECT usuario,contrasena FROM usuarios WHERE usuario = '$usuario' AND contrasena = '$contrasena';";
                    $result = $conn->query($sql);
                    if ($result->num_rows > 0) {
                        $key = "soa_key";
                        $token = array(
                            "iss" => "http://soa.org",
                            "aud" => "http://soa.com",
                            "iat" => 1356999524,
                            "nbf" => 1357000000,
                        );
                        $jwt = JWT::encode($token, $key);
                        //Set sql statement
                        $sql = "UPDATE usuarios SET token = ? WHERE usuario = ?";
                        //Prepare statement 
                        $stmt = $conn->prepare($sql);
                        if(!$stmt) {
                            return 'Error: '.$conn->error;
                        }else{
                        //Bind parameters
                            $stmt->bind_param('ss',$jwt, $usuario);
                            $stmt->execute();
                            return $jwt;
                        }
                    }else{
                        return "0";
                    }
                }
            ],
            
        ],
    ]);


    $mutationType = new ObjectType([
        'name' => 'Mutation',
        'fields' => [
            'crearUsuario' => [
                'type' => Type::string(),
                'args' => [
                    'usuario' => ['type' => Type::string()],
                    'nombre' => ['type' => Type::string()],
                    'contrasena' => ['type' => Type::string()],
                ],
                'resolve' => function ($root, $args) {
                    $usuario = $args['usuario'];
                    $nombre = $args['nombre'];
                    $contrasena = $args['contrasena'];

                    global $conn;

                    //Set sql statement
                    $sql = "INSERT INTO usuarios(usuario,nombre,contrasena) VALUES (?, ?, ?)";
                    
                    //Prepare statement 
                    $stmt = $conn->prepare($sql);
                    if(!$stmt) {
                        return 'Error: '.$conn->error;
                    }else{
                    //Bind parameters
                        $stmt->bind_param('sss',$usuario, $nombre, $contrasena );
                        $stmt->execute();
                        return "ok";
                    }
                },
            ],
            'salir' => [
                'type' => Type::string(),
                'args' => [
                    'usuario' => ['type' => Type::string()]
                ],
                'resolve' => function ($root, $args) {
                    $usuario = $args['usuario'];

                    global $conn;

                    //Set sql statement
                    $sql = "UPDATE usuarios SET token = NULL WHERE usuario = ?";
                    
                    //Prepare statement 
                    $stmt = $conn->prepare($sql);
                    if(!$stmt) {
                        return 'Error: '.$conn->error;
                    }else{
                    //Bind parameters
                        $stmt->bind_param('s',$usuario );
                        $stmt->execute();
                        return "ok";
                    }
                },
            ],
            'actualizarUsuario' => [
                'type' => Type::string(),
                'args' => [
                    'usuario' => ['type' => Type::string()],
                    'contrasena' => ['type' => Type::string()],
                ],
                'resolve' => function ($root, $args) {
                    $usuario = $args['usuario'];
                    $contrasena = $args['contrasena'];

                    global $conn;

                    //Set sql statement
                    $sql = "UPDATE usuarios SET contrasena = ? WHERE usuario = ?";
                    //Prepare statement 
                    $stmt = $conn->prepare($sql);
                    if(!$stmt) {
                        return 'Error: '.$conn->error;
                    }else{
                    //Bind parameters
                        $stmt->bind_param('ss',$contrasena, $usuario);
                        $stmt->execute();
                        return "ok";
                    }
                },
            ],
        ],
    ]);

    

    // See docs on schema options:
    // http://webonyx.github.io/graphql-php/type-system/schema/#configuration-options
    $schema = new Schema([
        'query' => $queryType,
        'mutation' => $mutationType
    ]);
    //gets the root of the sent json {"query":"query{accidentsData(...)}"}
    $rawInput = file_get_contents('php://input');
    //decodes the content as JSON
    $input = json_decode($rawInput, true);
    //takes the "query" property of the object
    $query = $input['query'];
    //checks if the input variables are a set
    $variableValues = isset($input['variables']) ? $input['variables'] : null;
    //calls the graphQL PHP libraty execute query with the prepared variables
    $result = GraphQL::executeQuery($schema, $query, null, null, $variableValues);
    //converts the result to a PHP array
    $output = $result->toArray();
} catch(\Exception $e) {
    $output = [
        'error' => [
            'message' => $e->getMessage()
        ]
    ];
}

header('Content-Type: application/json; charset=UTF-8');
echo json_encode($output);


