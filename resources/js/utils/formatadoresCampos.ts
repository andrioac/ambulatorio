const extrairDigitos = (valor: string, limite: number): string => valor.replace(/\D/g, '').slice(0, limite);

export const formatarCpf = (valor: string): string => {
    const digitos = extrairDigitos(valor, 11);

    if (digitos.length <= 3) {
        return digitos;
    }

    if (digitos.length <= 6) {
        return `${digitos.slice(0, 3)}.${digitos.slice(3)}`;
    }

    if (digitos.length <= 9) {
        return `${digitos.slice(0, 3)}.${digitos.slice(3, 6)}.${digitos.slice(6)}`;
    }

    return `${digitos.slice(0, 3)}.${digitos.slice(3, 6)}.${digitos.slice(6, 9)}-${digitos.slice(9)}`;
};

export const formatarCep = (valor: string): string => {
    const digitos = extrairDigitos(valor, 8);

    return digitos.length > 5 ? `${digitos.slice(0, 5)}-${digitos.slice(5)}` : digitos;
};

export const formatarTelefone = (valor: string): string => {
    const digitos = extrairDigitos(valor, 11);

    if (digitos.length === 0) {
        return '';
    }

    if (digitos.length < 3) {
        return `(${digitos}${digitos.length === 2 ? ')' : ''}`;
    }

    const ddd = digitos.slice(0, 2);
    const numero = digitos.slice(2);
    const tamanhoPrefixo = numero.length > 8 ? 5 : 4;
    const prefixo = numero.slice(0, tamanhoPrefixo);
    const sufixo = numero.slice(tamanhoPrefixo);

    return `(${ddd}) ${prefixo}${sufixo ? `-${sufixo}` : ''}`;
};

export const formatarCnpj = (valor: string): string => {
    const alfanumericos = valor.toUpperCase().replace(/[^A-Z0-9]/g, '');
    const base = alfanumericos.slice(0, 12);
    const digitosVerificadores = alfanumericos.slice(12).replace(/\D/g, '').slice(0, 2);
    const normalizado = `${base}${digitosVerificadores}`;

    if (normalizado.length <= 2) {
        return normalizado;
    }

    let formatado = `${normalizado.slice(0, 2)}.${normalizado.slice(2, 5)}`;

    if (normalizado.length > 5) {
        formatado += `.${normalizado.slice(5, 8)}`;
    }

    if (normalizado.length > 8) {
        formatado += `/${normalizado.slice(8, 12)}`;
    }

    if (normalizado.length > 12) {
        formatado += `-${normalizado.slice(12, 14)}`;
    }

    return formatado;
};

export const formatarCns = (valor: string): string => {
    const digitos = extrairDigitos(valor, 15);
    const blocos = [digitos.slice(0, 3), digitos.slice(3, 7), digitos.slice(7, 11), digitos.slice(11, 15)].filter(Boolean);

    return blocos.join(' ');
};
