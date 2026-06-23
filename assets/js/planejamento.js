const custoIngresso = 265;

// ETAPAS
function proximaEtapa(numero) {

    document
        .querySelectorAll(".etapa")
        .forEach(etapa => {
            etapa.classList.remove("ativa");
        });

    document
        .getElementById(`etapa${numero}`)
        .classList.add("ativa");

    atualizarTimeline(numero);
    atualizarResumo();
}

function voltarEtapa(numero) {

    document
        .querySelectorAll(".etapa")
        .forEach(etapa => {
            etapa.classList.remove("ativa");
        });

    document
        .getElementById(`etapa${numero}`)
        .classList.add("ativa");

    atualizarTimeline(numero);
}

// TIMELINE
function atualizarTimeline(numero){

    const steps =
        document.querySelectorAll(".step");

    steps.forEach((step,index)=>{

        step.classList.remove("ativo");

        if(index + 1 <= numero){

            step.classList.add("ativo");

        }

    });

}

// RESUMO
function atualizarResumo(){

    const orcamento =
        Number(
            document.getElementById("orcamento").value
        ) || 0;

    const transporte =
        Number(
            document.getElementById("transporte").value
        ) || 0;

    const hospedagem =
        Number(
            document.getElementById("hospedagem").value
        ) || 0;

    const total =
        custoIngresso +
        transporte +
        hospedagem;

    const restante =
        orcamento -
        total;

    // ETAPA 2

    const valorOrcamento =
        document.getElementById("valorOrcamento");

    if(valorOrcamento){

        valorOrcamento.textContent =
            `R$ ${orcamento.toFixed(2)}`;

    }

    // ETAPA 3

    const restanteBox =
        document.getElementById("restante");

    if(restanteBox){

        restanteBox.textContent =
            `R$ ${restante.toFixed(2)}`;

    }

    // ETAPA 4

    const resumoTransporte =
        document.getElementById("resumoTransporte");

    const resumoHospedagem =
        document.getElementById("resumoHospedagem");

    const totalFinal =
        document.getElementById("totalFinal");

    if(resumoTransporte){

        resumoTransporte.textContent =
            `R$ ${transporte.toFixed(2)}`;

    }

    if(resumoHospedagem){

        resumoHospedagem.textContent =
            `R$ ${hospedagem.toFixed(2)}`;

    }

    if(totalFinal){

        totalFinal.textContent =
            `R$ ${total.toFixed(2)}`;

    }

}

// BOTÕES DE OPÇÃO

const opcoes =
    document.querySelectorAll(".opcao");

opcoes.forEach(opcao => {

    opcao.addEventListener("click", () => {

        const grupo =
            opcao.parentElement;

        grupo
            .querySelectorAll(".opcao")
            .forEach(btn => {

                btn.classList.remove("ativa");

            });

        opcao.classList.add("ativa");

    });

});

// ATUALIZAÇÃO AUTOMÁTICA

const inputs =
    document.querySelectorAll(
        "#orcamento, #transporte, #hospedagem"
    );

inputs.forEach(input => {

    input.addEventListener(
        "input",
        atualizarResumo
    );

});

// VALIDAÇÃO DO ORÇAMENTO

const campoOrcamento =
    document.getElementById("orcamento");

if(campoOrcamento){

    campoOrcamento.addEventListener(
        "change",
        () => {

            const valor =
                Number(campoOrcamento.value);

            if(valor < custoIngresso){

                alert(
                    `O orçamento mínimo para este evento é R$ ${custoIngresso}.`
                );

            }

        }
    );

}

// INICIALIZAÇÃO

document.addEventListener(
    "DOMContentLoaded",
    () => {

        atualizarTimeline(1);
        atualizarResumo();

    }
);