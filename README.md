## Install
For posterity, since this is already a dist, I'm including the /vendor folder so nothing needs to be installed. But if you need to update dependencies, use composer and install the openai-php client:
```composer require openai-php/client```

### Snippet Post Type
Each snippet has a few fields:
- Instruction text: custom instructions for this snippet
- Reference text: text that GPT references in order to rewrite the SEO text
- SEO text: the text that GPT has generated
- Post content: this is the actual content displayed on the website. Once the post is updated from "draft" -> "publish" the text that is in SEO text replaces the current post content

### TODO
- Allow GPT to output html
- Allow easy changing of models
- Default to daily recurrence
- Improve GPT voice with prompt (it's too detectable right now)
- Integrate custom instructions