<div class="flex items-center gap-4 mr-4">
    <a href="/lang/my" 
       class="font-bold hover:text-primary-500 {{ app()->getLocale() === 'my' ? 'text-primary-600' : 'text-gray-500' }}">
       MM
    </a>
    <span class="text-gray-300">|</span>
    <a href="/lang/en" 
       class="font-bold hover:text-primary-500 {{ app()->getLocale() === 'en' ? 'text-primary-600' : 'text-gray-500' }}">
       EN
    </a>
</div>