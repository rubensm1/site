<?php 

/*
CREATE TABLE Mensagem (
    id INT PRIMARY KEY AUTO_INCREMENT,
    sequencial INT NOT NULL,
    sinal VARCHAR(64),
    texto VARCHAR(255) NOT NULL
)
*/
include 'Connexao.php';
include 'Model.php';
include 'Mensagem.php';
?>
<html>
<head>
	<title>Título lol</title>
</head>
<body>
	<?php
		$mm = new Mensagem(array("sequencial"=>1, "sinal"=>"aaa", "texto"=>"Teste de Mensagem"));
		var_dump($mm);
		//$mm->persist();
	?>
</body>
</html>