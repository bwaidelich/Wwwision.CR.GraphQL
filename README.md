# Wwwision.CR.GraphQL
Simple GraphQL Adapter for the Neos Content Repository

## Description

This package provides a simple GraphQL API for Neos Content Repositories.
It can be used in a Neos distribution or with a standalone Content Repository.

**Disclaimer:** This is merely an experiment. Feel free to use it or copy and adjust it to your needs, but
please be aware of the limitations:

### Limitations

* The API just provides **read access** to nodes of the **live workspace**
  (this might change slightly in the future, but this won't become a fully fledged CR API!)
* The API is just a slim wrapper on top of the Content Repository PHP API. Other than the default Neos
  rendering, there is no caching in place yet!
* CR nodes can be nested infinitely, GraphQL queries can't (see examples below)

## Installation

Install this package via [composer](https://getcomposer.org/):
```
composer require wwwision/cr-graphql
```

### Routes

This package comes with corresponding routes, but they won't be active by default.
This can be changed via some `Settings.yaml`:

```yaml
Neos:
  Flow:
    mvc:
      routes:
        'Wwwision.CR.GraphQL':
          position: 'start'
          variables:
            path: 'graphql'
```

*Note:* The `path` variable defines the URL path, the GraphQL API will be exposed to, with the above example
this will be `https://your-server.tld/graphql`.

### Adjust policies

If installed in a [Neos](https://neos.io) distribution, the GraphQL controller is usually
not allowed to be called by unauthenticated users.
This can be changed with the following lines in a `Configuration/Policy.yaml` file:

```yaml
privilegeTargets:

  'Neos\Flow\Security\Authorization\Privilege\Method\MethodPrivilege':

    'Some.Package:GraphQL.Endpoint':
      matcher: 'method(t3n\GraphQL\Controller\GraphQLController->queryAction(endpoint=="Wwwision_CR_GraphQL"))'

roles:

  'Neos.Flow:Everybody':
    privileges:
      -
        privilegeTarget: 'Some.Package:GraphQL.Endpoint'
        permission: GRANT
```

## Usage

If installed correctly, you should be able to query the GraphQL endpoint.
You can try it via cURL:

```
curl 'http://localhost:8081/graphql' -H 'content-type: application/json' --data-binary '{"operationName":null,"variables":{},"query":"{rootNode {identifier}}"}'
```

This should return something like
```json
{"data":{"rootNode":{"identifier":"a1839a7e-8600-4ff3-ab9e-27d54fd8b3d9"}}}
```

### Node properties

The properties of a node are represented via a `NodeProperties` scalar.
In practice this means, that the properties will be converted to plain JSON in the result.
This package uses the [Symfony Serializer](https://symfony.com/doc/current/components/serializer.html) to convert
non-scalar properties.
The common object types (DateTime, node references, assets & images) are covered by custom Normalizers.
You can easily configure additional types or change the behavior of the existing ones.

#### Add custom normalizers

This package already provides a `AssetNormalizer` that converts asset to an JSON object like:
```json
{
  "title": "<title of the asset>",
  "mediaType": "<media type of the asset>",
  "url": "<absolute url of the published asset>"
}
```

To add a custom conversion for Video assets we could create a new Normalizer:
```php
<?php
namespace Your\Package;

use Neos\Media\Domain\Model\Video;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class AssetNormalizer implements NormalizerInterface
{

    public function supportsNormalization($data, string $format = null)
    {
        return $data instanceof Video;
    }

    /**
     * @param Video $video
     * @param string|null $format
     * @param array $context
     * @return array|\ArrayObject|bool|float|int|string|void|null
     */
    public function normalize($video, string $format = null, array $context = [])
    {
        return [
            'id' => $video->getIdentifier(),
            'title' => $video->getTitle(),
            'width' => $video->getWidth(),
            'height' => $video->getHeight(),
        ];
    }

}
```

And register it via `Settings.yaml`:

```yaml
Wwwision:
  CR:
    GraphQL:
      normalizers:
        'YourVideoNormalizer':
          className: 'Your\Package\VideoNormalizer'
          position: 'start'
```

*Note:* We need to set the `position` to "start" so that this new normalizer is evaluated before the existing
`AssetNormalizer` (since that also supports `Video` properties).

#### Replace existing normalizers

If you want to replace/remove an existing normalizer you can do so by overriding the corresponding settings:

```yaml
Wwwision:
  CR:
    GraphQL:
      normalizers:
        # disable provided image normalizer
        'Image': ~
        # change implementation of provided node normalizer
        'Node':
          className: 'Your\Package\SomeOtherImplementation'
```

### Example Queries

A couple of example GraphQL queries:

#### Get a single Node by its id

```graphql
{
  node(identifier: "6db34628-60c7-4c9a-f6dd-54742816039e") {
    identifier
    name
    type
    properties
  }
}
```

The result on the [Neos.Demo](https://github.com/neos/Neos.Demo) site would be something like:

```json
{
  "data": {
    "node": {
      "identifier": "6db34628-60c7-4c9a-f6dd-54742816039e",
      "name": "i-down-the-rabbit-hole",
      "type": "Neos.Demo:Document.Chapter",
      "properties": {
        "title": "I. Down the Rabbit-hole",
        "chapterImage": {
          "width": 359,
          "height": 500,
          "url": "http://localhost:8081/_Resources/Persistent/3/0/d/0/30d0d71c6e7e4dd53636a8b9a5d5c8fd9b73f10f/alice-1.jpg"
        },
        "chapterDescription": "Alice was beginning to get very tired of sitting by her sister on the bank, and of having nothing to do: once or twice she had peeped into the book her sister was reading, but it had no pictures or conversations in it, \"and what is the use of a book,\" thought Alice, \"without pictures or conversations?\"",
        "layout": "chapter",
        "uriPathSegment": "i-down-the-rabbit-hole"
      }
    }
  }
}
```

#### Get all document nodes recursively

GraphQL doesn't support recursive queries ([for some good reasons](https://github.com/graphql/graphql-spec/issues/91#issuecomment-254895093))
but it's possible to use fragments in order to get around that limitation.
The following query will fetch the root node (`/sites`), all site nodes (for example `/sites/neosdemo`) and then all nodes
below that implement the `Neos.Neos:Document` node type up to 5 levels:

```graphql
{
  rootNode {
    sites: childNodes {
      site: childNodes {
        name
        childNodes(filter: "Neos.Neos:Document") {
          ...NodesRecursive
        }
      }
    }
  }
}
fragment NodesRecursive on Node {
  ...NodeFields
  childNodes(filter: "Neos.Neos:Document") {
    ...NodeFields
    childNodes(filter: "Neos.Neos:Document") {
      ...NodeFields
      childNodes(filter: "Neos.Neos:Document") {
        ...NodeFields
        childNodes(filter: "Neos.Neos:Document") {
          ...NodeFields
        }
      }
    }
  }
}
fragment NodeFields on Node {
  identifier
  name
  type
  properties
}
```

The result could look like this:
```json
{
  "data": {
    "rootNode": {
      "sites": [
        {
          "site": [
            {
              "name": "neosdemo",
              "childNodes": [
                {
                  "identifier": "e35d8910-9798-4c30-8759-b3b88d30f8b5",
                  "name": "home",
                  "type": "Neos.Neos:Shortcut",
                  "properties": {
                    "title": "Home",
                    "targetMode": "parentNode",
                    "uriPathSegment": "home",
                    "metaRobotsNoindex": true
                  },
                  "childNodes": []
                },
                {
                  "identifier": "a3474e1d-dd60-4a84-82b1-18d2f21891a3",
                  "name": "features",
                  "type": "Neos.Demo:Document.LandingPage",
                  "properties": {
                    "title": "Features",
                    "uriPathSegment": "features"
                  },
                  "childNodes": [
                    {
                      "identifier": "b082c6b6-8a64-4786-b767-d62ef22209b1",
                      "name": "shortcuts",
                      "type": "Neos.Demo:Document.Page",
                      "properties": {
                        "title": "Shortcuts",
                        "uriPathSegment": "shortcuts"
                      },
                      "childNodes": [],
...
```

#### Get all content nodes on a given document node, recursively

As mentioned above, endless recursion is not possible.
But with the following query you can fetch all content and content collection nodes
underneath the node with the specified identifier up to 5 levels:

```graphql
query Nodes(
  $rootIdentifier: NodeIdentifier!
  $nodeTypeConstraints: NodeTypeConstraints
) {
  node(identifier: $rootIdentifier) {
    ...NodesRecursive
  }
}
fragment NodesRecursive on Node {
  ...NodeFields
  childNodes(filter: $nodeTypeConstraints) {
    ...NodeFields
    childNodes(filter: $nodeTypeConstraints) {
      ...NodeFields
      childNodes(filter: $nodeTypeConstraints) {
        ...NodeFields
        childNodes(filter: $nodeTypeConstraints) {
          ...NodeFields
        }
      }
    }
  }
}
fragment NodeFields on Node {
  identifier
  name
  type
  properties
}
```

With the following variables:

```json
{
  "rootIdentifier":"a3474e1d-dd60-4a84-82b1-18d2f21891a3",
  "nodeTypeConstraints": "Neos.Neos:Content,Neos.Neos:ContentCollection"
}
```
