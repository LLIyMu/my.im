document.querySelector('.sitemap-button').onclick = (e) => {

    e.preventDefault();

    createSitemap();
}

links_counter = 0;

function createSitemap() {

    links_counter++;

    Ajax({data: {ajax:'sitemap', links_counter: links_counter}})
        .then((res) =>{
            console.log('успех -' + res);
            console.log(links_counter);
        })
        .catch((res) => {
            console.log(links_counter);
            console.log('ошибка -' + res);
            createSitemap();
        });
}
