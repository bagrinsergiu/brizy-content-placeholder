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

This can easily achieved by replacing the string with str_replace but what if you have 100  placeholders and some of them must get some info from other resources like a DB or an API. 


### Architecture

Few words about the classes you will work with

* Registry - add more info

* Replacer - add more info

* Placeholder - add more info

* Context - add more info


### Getting complex

- Placeholders attributes
- Repeated content

### Code samples



   
  


