<?php

session_start();

require './assets/config/conexao.php';


// Verifica login
$id_user = $_SESSION['id_user']
    ?? ($_SESSION['usuario']['id_user'] ?? null);

if (!$id_user) {
    die('Você precisa estar logado para editar uma avaliação.');
}


// Pega o ID da avaliação
$id_avaliacao = filter_input(
    INPUT_GET,
    'id_avaliacao',
    FILTER_VALIDATE_INT
);

if (!$id_avaliacao) {
    die('Avaliação não informada.');
}


// Busca a avaliação
$sql = "SELECT
            av.id_avaliacao,
            av.id_user,
            av.id_evento,
            av.nota,
            av.comentario,
            av.data_avaliacao,
            e.nome_evento

        FROM avaliacao av

        INNER JOIN evento e
            ON e.id_evento = av.id_evento

        WHERE av.id_avaliacao = ?
        AND av.id_user = ?

        LIMIT 1";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
    'ii',
    $id_avaliacao,
    $id_user
);

$stmt->execute();

$resultado = $stmt->get_result();

$avaliacao = $resultado->fetch_assoc();

$stmt->close();


if (!$avaliacao) {
    die('Avaliação não encontrada ou você não tem permissão para editá-la.');
}

?>

<!doctype html>

<html lang="pt-BR">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Editar avaliação - ShowMe</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
        rel="stylesheet"
    >

    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet"
    >

    <style>

        body {
            font-family: 'Poppins', sans-serif;
            background: #111;
            color: white;
        }

        .editar-container {
            max-width: 700px;
            margin: 60px auto;
            padding: 30px;
        }

        .editar-card {
            background: #1c1c1c;
            border-radius: 18px;
            padding: 35px;
        }

        .editar-card h1 {
            margin-bottom: 10px;
        }

        .evento-nome {
            color: #a8ff00;
            margin-bottom: 30px;
        }

        .estrelas {
            display: flex;
            gap: 8px;
            margin: 15px 0 25px;
        }

        .estrela {
            font-size: 30px;
            cursor: pointer;
            color: #777;
        }

        .estrela.ativa {
            color: #a8ff00;
        }

        textarea {
            width: 100%;
            min-height: 160px;
            resize: vertical;
            background: #111;
            color: white;
            border: 1px solid #444;
            border-radius: 10px;
            padding: 15px;
        }

        label {
            display: block;
            margin-top: 15px;
            margin-bottom: 8px;
        }

        .botoes {
            display: flex;
            gap: 10px;
            margin-top: 25px;
        }

        .btn-salvar {
            background: #a8ff00;
            color: #111;
            border: none;
            padding: 12px 22px;
            border-radius: 8px;
            font-weight: 600;
        }

        .btn-voltar {
            background: transparent;
            color: white;
            border: 1px solid #555;
            padding: 12px 22px;
            border-radius: 8px;
            text-decoration: none;
        }

    </style>

</head>

<body>

<div class="editar-container">

    <div class="editar-card">

        <h1>
            <i class="bi bi-pencil"></i>
            Editar avaliação
        </h1>

        <p class="evento-nome">
            <?= htmlspecialchars($avaliacao['nome_evento']) ?>
        </p>


        <form id="formEditarAvaliacao">

            <input
                type="hidden"
                name="id_avaliacao"
                value="<?= (int)$avaliacao['id_avaliacao'] ?>"
            >

            <input
                type="hidden"
                name="id_evento"
                value="<?= (int)$avaliacao['id_evento'] ?>"
            >


            <label>Sua nota:</label>

            <div class="estrelas">

                <?php for ($i = 1; $i <= 5; $i++): ?>

                    <i
                        class="bi <?= $i <= $avaliacao['nota']
                            ? 'bi-star-fill ativa'
                            : 'bi-star' ?> estrela"
                        data-valor="<?= $i ?>"
                    ></i>

                <?php endfor; ?>

            </div>


            <input
                type="hidden"
                name="nota"
                id="nota"
                value="<?= (int)$avaliacao['nota'] ?>"
            >


            <label for="comentario">
                Seu comentário:
            </label>

            <textarea
                name="comentario"
                id="comentario"
                required
            ><?= htmlspecialchars($avaliacao['comentario']) ?></textarea>


            <div class="botoes">

                <a
                    href="detalhesEvento.php?id_evento=<?= (int)$avaliacao['id_evento'] ?>#avaliacoes"
                    class="btn-voltar"
                >
                    Cancelar
                </a>

                <button
                    type="submit"
                    class="btn-salvar"
                >
                    <i class="bi bi-check-lg"></i>
                    Salvar alterações
                </button>

            </div>

        </form>

    </div>

</div>


<script>

const estrelas = document.querySelectorAll('.estrela');

const campoNota = document.getElementById('nota');


estrelas.forEach(estrela => {

    estrela.addEventListener('click', function() {

        const valor = Number(
            this.dataset.valor
        );

        campoNota.value = valor;


        estrelas.forEach(item => {

            const valorItem =
                Number(item.dataset.valor);

            item.classList.toggle(
                'bi-star-fill',
                valorItem <= valor
            );

            item.classList.toggle(
                'bi-star',
                valorItem > valor
            );

            item.classList.toggle(
                'ativa',
                valorItem <= valor
            );

        });

    });

});

document.getElementById('formEditarAvaliacao').addEventListener('submit', async (evento) => {
    evento.preventDefault();

    const idAvaliacao = Number(document.querySelector('[name="id_avaliacao"]').value);
    const idEvento = Number(document.querySelector('[name="id_evento"]').value);

    try {
        const resposta = await fetch(`api/avaliacoes/${idAvaliacao}`, {
            method: 'PUT',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                nota: Number(campoNota.value),
                comentario: document.getElementById('comentario').value
            })
        });
        const dados = await resposta.json();

        if (!resposta.ok) {
            throw new Error(dados.erro || 'Não foi possível atualizar a avaliação.');
        }

        window.location.href = `detalhesEvento.php?id_evento=${idEvento}#avaliacoes`;
    } catch (erro) {
        alert(erro.message);
    }
});

</script>

</body>

</html>
