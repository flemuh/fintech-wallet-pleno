import { shallowReactive, watch, type Ref } from 'vue'

/**
 * Decide quando mostrar o erro de cada campo, no padrão recomendado de UX:
 * - o erro só aparece depois que a pessoa sai do campo preenchido ou tenta enviar;
 * - daí em diante o campo é revalidado a cada digitação, e o erro some assim que fica certo;
 * - o erro que veio da API some quando o próprio campo é editado.
 */
export function useCampos<Campo extends string>(
  valores: Record<Campo, string>,
  regras: Record<Campo, () => string | null>,
  errosDaApi: Ref<Record<string, string[]>>,
) {
  const nomes = Object.keys(regras) as Campo[]
  // Raso de propósito: o Set só guarda nomes de campo, não há nada dentro para tornar reativo
  const tocados = shallowReactive(new Set<Campo>())

  // Sair de um campo vazio não acusa erro: a pessoa pode só estar passando por ele
  function sair(campo: Campo): void {
    if (valores[campo] !== '') tocados.add(campo)
  }

  function erroDo(campo: Campo): string | undefined {
    const erroDaTela = tocados.has(campo) ? regras[campo]() : null

    return erroDaTela ?? errosDaApi.value[campo]?.[0]
  }

  function valido(campo: Campo): boolean {
    return tocados.has(campo) && !erroDo(campo)
  }

  /** Marca todos os campos e devolve se o formulário pode ser enviado. */
  function validarTudo(): boolean {
    nomes.forEach((campo) => tocados.add(campo))

    return nomes.every((campo) => !regras[campo]())
  }

  for (const campo of nomes) {
    watch(
      () => valores[campo],
      () => {
        errosDaApi.value = Object.fromEntries(
          Object.entries(errosDaApi.value).filter(([nome]) => nome !== campo),
        )
      },
    )
  }

  return { sair, erroDo, valido, validarTudo }
}
