# Brizy content placeholders sdk

Let's assume we have an email template that must be sent for many users and this template must contain the username and some other dynamic info.  

Email template:
```
Hi {{username}}
I wanted to personally welcome you to {{company-name}}
If you have any questions, you can always email us to {{our-email}}

Best Regards.
```

As you can see the template above contains three placeholders *username*, *company-name* and *our-email*.

This can easily achieved by replacing the strings with str_replace but what if you have 100  placeholders and some of them must get info from resources like a DB or an API. 


### Architecture

Few words about the classes you will work with

#### Registry Class
A class that manage the palceholders. You can register or obtain placeholders. See the examples blow.

#### Placeholder Interface
All placeholders must implement this interface.

The `getValue` method must return the string that will replace the placeholder. This method receive a context and the content placeholder object (An object that contain all the info  about the placeholder found in the original content)

The `support` method must return true if the class can handle the placeholder. 

#### Context Interface
There are cases when the placeholder will need some specific info like the current page or current request, session, etc..  all these objects must be passed in a context object.

#### Replacer Class
The class has only one method: replacePlaceholders. Self explanatory :).


### Get Started

#### Registering a placeholder

````
$registry->registerPlaceholder(new TitlePlaceholder());
$registry->registerPlaceholder(new AuthorPlaceholder());
````


#### Placeholders with attributes

```{{placeholder_name attr="value1"}}```

```
class SamplePlaceholder extends AbstractPlaceholder
{
    const NAME = 'placeholder_name';

    public function support($placeholderName)
    {
        return $placeholderName==self::NAME;
    }

    public function getValue(ContextInterface $context, ContentPlaceholder $placeholder)
    {
        // getting the attributes
        $atributes = $placeholder->getAttributes();

        // use the attribute
        $value = $atributes['attr'];

        // smart code goes here

        return 'placeholder_value';
    }
}

```


#### Repeated wit content
Imagine you have a placeholder that must iterate thought a list of pages and repeat a chunk of html for each page.

```
{{page_loop filter="some_values"}}
<div>
    <h1>{{page_title}}</h2>
    <p>{{page_excerpt}}</p>
</div>
{{end_page_loop}}
```

To be able to make this loop we will have to inject the repository and the replacer class.
We assume here that placeholders page_title and page_excerpt are already registered and use the page from context to get the value.

```
class PostLoop extends AbstractPlaceholder
{
    const NAME = 'page_loop';

    public $repository;

    public $replacer;

    public function __construct(PageRepository $repository, Replacer $replacer ) {
        $this->repository = $repository;  
        $this->replacer = $replacer;  
    }

    public function support($placeholderName)
    {
        return $placeholderName==self::NAME;
    }

    public function getValue(ContextInterface $context, ContentPlaceholder $placeholder)
    {
        // getting the attributes
        $atributes = $placeholder->getAttributes();

        // use the attribute
        $pages = $this->repository->getPagesByFilter($atributes['filter']);

        $placeholderContent = $placeholder->getContent();

        $content = ob_start();

        foreach($pages as $page) {

            // we asume the PostLoopContext is already implemented somewhere
            // it is just a simple class with one property.
            $context = new PostLoopContext($page); 

            $this->replacer->->replacePlaceholders($placeholderContent, $context);
        }
        return ob_get_clean();
    }
}
```



#### Placeholder class that can handle more placeholders
There are cases when you have placeholders for a set of properties of a class.
```
{{ page_title }}
{{ page_excerpt }}
{{ page_content }}
{{ page_author }}
...
```

This can be implemented in a single class.

```
class PagePropertyPlaceholder extends AbstractPlaceholder
{
    const PREFIX = 'page_loop';
    
    public function support($placeholderName)
    {
        return strpos($placeholderName,self::PREFIX)===0;
    }
    
    public function getValue(ContextInterface $context, ContentPlaceholder $placeholder)
    {
        $page = $context->getPage();
    
        $sufix = $this->getSufix($placeholder->getName());
    
        switch($sufix) {
            case 'title': return $page->getTitle();
            case 'excerpt': return $page->getExcerpt();
            //....
        }
    }
    
    private function getSufix($placeholderName) {
        return str_replace(self::PREFIX,'',$placeholderName);
    } 
}

```

You can register these placegolders like this:

```
$registry->registerPlaceholder(new PagePropertyPlaceholder());
$registry->registerPlaceholder(new PagePropertyPlaceholder());
```

  


