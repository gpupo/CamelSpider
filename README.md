
Camel Spider é uma [aranha](http://www.camel-spiders.net/) que coleta links e conteúdo de sites.

Esse conteúdo é filtrado a partir de palavras chaves.

A spider navega nestes sites, coleta os links que fazem parte do escopo do domínio e faz o processamento interno dessa informação.

A Camel Spider é um componente a ser utilizado por uma aplicação que gerencie a base de assinaturas e receba o retorno do processamento para armazenamento em banco de dados.

A Camel Spider utiliza componentes do Zend Framework 2, Docrine Common e Goutte, e presume que será instânciada dentro de um projeto com o autoloading corretamente configurado de segundo a PRS-0.

Queremos que a Camel Spider seja uma Spider Web que supra necessidades de projetos em PHP.

O [Camel Spider Bundle](http://github.com/gpupo/CamelSpiderBundle) integra a Camel Spider ao Symfony 2 e gerencia as assinaturas e o cache de informações.


## Otimização e cache

A cada requisição é consumido memória.
Para evitar isso, os objetos são cacheados em disco, portanto é
necessário informar um objeto cache onde seja possível gravar e
recuperar informações a partir de um hash.


O objeto gravado é serializado pelo componente [Zend Cache](http://framework.zend.com/manual/en/zend.cache.html).
Este objeto é passado ao construtor da Camel Spider (Dependency
injection).

Você pode implementar este serviço de cache mas também pode utilizar o  [Camel Spider Bundle](http://github.com/gpupo/CamelSpiderBundle) como referência de implementação.


Os objetos capturados são retornados pela processamento inicial, e são
acessados diretamente do cache, pela aplicação que os utilizarão.

## Você pode contribuir com o projeto!

Este README precia de tradução e ainda temos muito trabalho pela frente e gostaríamos da sua ajuda.
 
