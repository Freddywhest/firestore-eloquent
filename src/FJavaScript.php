<?php

namespace Roddy\FirestoreEloquent;

trait FJavaScript
{
    private function loadJavascriptForLivewirePagination()
    {
        return "
        <script>
            document.addEventListener('livewire:initialized', (e) => {
                let fpaginationLoading = [];
                let fpaginationLoadingDisable = [];
                let islivewirepaginationavailable = document.querySelectorAll('[islivewirepaginationavailable]');
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
                function paginationProccess ()
                {
                    let fPaginationPageNameArr = JSON.parse(window.localStorage.getItem('fpaginationpagename')) || {};

                    window.addEventListener('popstate', function (e) {
                        const params = new URLSearchParams(window.location.search);

                        if(Object.entries(fPaginationPageNameArr).length > 0){
                            Object.entries(fPaginationPageNameArr).forEach(async (fPaginationPageName) => {
                                if(params.has(fPaginationPageName[0])){
                                    await Livewire.dispatch('gotoPageFromLivewireJavascript', {page: params.get(fPaginationPageName[0]), pageName: fPaginationPageName[0]});
                                }else{
                                    await Livewire.dispatch('resetPageFromLivewireJavascript', {pageName: fPaginationPageName[0]});
                                }
                            });
                        }
                    });
                }

                Livewire.hook('morph.adding',  ({ el, component }) => {
                    fpaginationLoadingDisable = document.querySelectorAll(`[fpagination\\\\:loading\\\\.disable]`);
                    fpaginationLoading = document.querySelectorAll('[fpagination\\\\:loading]');
                    islivewirepaginationavailable = document.querySelectorAll('[islivewirepaginationavailable]');


                    if(fpaginationLoading.length > 0){
                        fpaginationLoading.forEach((fpaginationLoadingEl) => {
                            fpaginationLoadingEl.style.display = 'none';
                        })
                    }

                    if(fpaginationLoadingDisable.length < 0){
                        fpaginationLoadingDisable.forEach((fpaginationLoadingDisableEl) => {
                            fpaginationLoadingDisableEl.style.display = 'block';
                        })
                    }

                    if(islivewirepaginationavailable.length > 0){
                        paginationProccess();
                    }
                })

                Livewire.hook('morph.added',  ({ el }) => {
                    fpaginationLoadingDisable = document.querySelectorAll(`[fpagination\\\\:loading\\\\.disable]`);
                    fpaginationLoading = document.querySelectorAll('[fpagination\\\\:loading]');
                    islivewirepaginationavailable = document.querySelectorAll('[islivewirepaginationavailable]');


                    if(fpaginationLoading.length > 0){
                        fpaginationLoading.forEach((fpaginationLoadingEl) => {
                            fpaginationLoadingEl.style.display = 'none';
                        })
                    }

                    if(fpaginationLoadingDisable.length < 0){
                        fpaginationLoadingDisable.forEach((fpaginationLoadingDisableEl) => {
                            fpaginationLoadingDisableEl.style.display = 'block';
                        })
                    }

                    if(islivewirepaginationavailable.length > 0){
                        paginationProccess();
                    }
                })

                Livewire.hook('request', ({ uri, options, payload, respond, succeed, fail }) => {
                    const methodsArray = JSON.parse(payload).components[0].calls[0]?.method || [];
                    const paramsArray = JSON.parse(payload).components[0].calls[0]?.params || [];
                    console.log(JSON.parse(payload));
                    if(fpaginationLoading.length > 0 && (['gotoPage', 'previousPage', 'nextPage', 'resetPage'].includes(methodsArray) || paramsArray.includes('gotoPageFromLivewireJavascript') || paramsArray.includes('resetPageFromLivewireJavascript'))){
                        fpaginationLoading.forEach((fpaginationLoadingEl) => {
                            fpaginationLoadingEl.style.display = 'block';
                        })
                    }

                    if(fpaginationLoading.length > 0 && ['gotoPage', 'previousPage', 'nextPage', 'resetPage'].indexOf(methodsArray) === -1 && paramsArray.indexOf('gotoPageFromLivewireJavascript') === -1 && paramsArray.indexOf('resetPageFromLivewireJavascript') === -1){
                        fpaginationLoading.forEach((fpaginationLoadingEl) => {
                            fpaginationLoadingEl.style.display = 'block';
                        })
                    }

                    if(fpaginationLoadingDisable.length > 0 && ['gotoPage', 'previousPage', 'nextPage', 'resetPage'].indexOf(methodsArray) === -1 && paramsArray.indexOf('gotoPageFromLivewireJavascript') === -1 && paramsArray.indexOf('resetPageFromLivewireJavascript') === -1){
                        fpaginationLoadingDisable.forEach((fpaginationLoadingDisableEl) => {
                            fpaginationLoadingDisableEl.style.display = 'none';
                        })
                    }

                    if(fpaginationLoadingDisable.length > 0 && (['gotoPage', 'previousPage', 'nextPage', 'resetPage'].includes(methodsArray) || paramsArray.includes('gotoPageFromLivewireJavascript') || paramsArray.includes('resetPageFromLivewireJavascript'))){
                        fpaginationLoadingDisable.forEach((fpaginationLoadingDisableEl) => {
                            fpaginationLoadingDisableEl.style.display = 'none';
                        })
                    }

                    succeed(({ status, json }) => {
                        let fPaginationPageNameArr = JSON.parse(window.localStorage.getItem('fpaginationpagename')) || {};
                        const params = new URLSearchParams(window.location.search);
                        /* if(['gotoPage', 'previousPage', 'nextPage', 'resetPage', '__lazyLoad'].indexOf(methodsArray) === -1 && paramsArray.indexOf('gotoPageFromLivewireJavascript') === -1 && paramsArray.indexOf('resetPageFromLivewireJavascript') === -1 && paramsArray.indexOf('resetPageFromLivewireJavascriptWithoutParameter') === -1 && ['__dispatch'].indexOf(methodsArray)){
                            if(Object.entries(fPaginationPageNameArr).length > 0){
                                Object.entries(fPaginationPageNameArr).forEach(async (fPaginationPageName) => {
                                    if(params.has(fPaginationPageName[0])){
                                        await Livewire.dispatch('gotoPageFromLivewireJavascript', {page: params.get(fPaginationPageName[0]), pageName: fPaginationPageName[0]});
                                    }else{
                                        await Livewire.dispatch('resetPageFromLivewireJavascriptWithoutParameter', {pageName: fPaginationPageName[0]});
                                    }
                                });
                            }
                        } */
                    })
                })
            })
        </script>
        ";
    }
}
