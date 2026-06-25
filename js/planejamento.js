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
function atualizarTimeline(numero) {

    const steps =
        document.querySelectorAll(".step");

    steps.forEach((step, index) => {

        step.classList.remove("ativo");

        if (index + 1 <= numero) {
            step.classList.add("ativo");
        }

    });

}

// RESUMO — atualiza todos os valores em todas as etapas
function atualizarResumo() {

    const orcamento =
        Number(document.getElementById("orcamento").value) || 0;

    const transporte =
        Number(document.getElementById("transporte").value) || 0;

    const hospedagem =
        Number(document.getElementById("hospedagem").value) || 0;

    const totalGastos =
        custoIngresso + transporte + hospedagem;

    const saldo =
        orcamento - totalGastos;

    // ── Etapa 2: orçamento disponível ──
    const valorOrcamento =
        document.getElementById("valorOrcamento");

    if (valorOrcamento) {
        valorOrcamento.textContent =
            formatarReal(orcamento);
    }

    // ── Etapa 3: orçamento restante (orcamento - ingresso - transporte) ──
    const restanteBox =
        document.getElementById("restante");

    if (restanteBox) {
        const restante = orcamento - custoIngresso - transporte;
        restanteBox.textContent = formatarReal(restante);
    }

    // ── Etapa 4: bloco Custos ──
    const resumoTransporte =
        document.getElementById("resumoTransporte");

    const resumoHospedagem =
        document.getElementById("resumoHospedagem");

    const totalFinal =
        document.getElementById("totalFinal");

    if (resumoTransporte) {
        resumoTransporte.textContent = formatarReal(transporte);
    }

    if (resumoHospedagem) {
        resumoHospedagem.textContent = formatarReal(hospedagem);
    }

    if (totalFinal) {
        totalFinal.textContent = formatarReal(totalGastos);
    }

    // ── Etapa 4: bloco Orçamento ──
    const resumoOrcamentoPrevisto =
        document.getElementById("resumoOrcamentoPrevisto");

    const resumoTotalGastos =
        document.getElementById("resumoTotalGastos");

    const resumoSaldo =
        document.getElementById("resumoSaldo");

    if (resumoOrcamentoPrevisto) {
        resumoOrcamentoPrevisto.textContent = formatarReal(orcamento);
    }

    if (resumoTotalGastos) {
        resumoTotalGastos.textContent = formatarReal(totalGastos);
    }

    if (resumoSaldo) {
        resumoSaldo.textContent = formatarReal(saldo);

        // Deixa o saldo vermelho se negativo
        resumoSaldo.style.color =
            saldo < 0 ? "#ff4444" : "";
    }

}

// HELPER — formata número como R$ X.XXX,XX
function formatarReal(valor) {
    return valor.toLocaleString("pt-BR", {
        style: "currency",
        currency: "BRL"
    });
}

// BOTÕES DE OPÇÃO (radio visual)
const opcoes =
    document.querySelectorAll(".opcao");

opcoes.forEach(opcao => {

    opcao.addEventListener("click", () => {

        const grupo = opcao.parentElement;

        grupo
            .querySelectorAll(".opcao")
            .forEach(btn => btn.classList.remove("ativa"));

        opcao.classList.add("ativa");

    });

});

// ATUALIZAÇÃO AUTOMÁTICA ao digitar
const inputs =
    document.querySelectorAll(
        "#orcamento, #transporte, #hospedagem"
    );

inputs.forEach(input => {
    input.addEventListener("input", atualizarResumo);
});

// VALIDAÇÃO DO ORÇAMENTO
const campoOrcamento =
    document.getElementById("orcamento");

if (campoOrcamento) {

    campoOrcamento.addEventListener("change", () => {

        const valor = Number(campoOrcamento.value);

        if (valor < custoIngresso) {
            alert(
                `O orçamento mínimo para este evento é ${formatarReal(custoIngresso)}.`
            );
        }

    });

}

// INICIALIZAÇÃO
document.addEventListener("DOMContentLoaded", () => {
    atualizarTimeline(1);
    atualizarResumo();
});
