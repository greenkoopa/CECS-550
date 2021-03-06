<?php

namespace CECS550\Http\Controllers;

use Illuminate\Http\Request;
use Cart;
use Auth;
use Notification;
use CECS550\Notifications\purchased;

class ShopController extends Controller
{
    public function displayCart()
    {
      \Stripe\Stripe::setApiKey(env('STRIPE_SECRET'));
      $content = Cart::content();

      foreach ($content as $cartItem)
      {
        $sku = \Stripe\SKU::retrieve($cartItem->id);
        $cartItem->pic = $sku->metadata->img_path;
        $cartItem->sku_description = $sku->metadata->description;
      }

      $details = [
        "content" => Cart::content(),
        "subtotal" => str_replace(',', '', Cart::subtotal()) / 100
      ];

      return view('shop.cart')->with($details);
    }

    public function addToCart($id)
    {
      \Stripe\Stripe::setApiKey(env('STRIPE_SECRET'));
      $sku = \Stripe\SKU::retrieve($id);
      $product = \Stripe\Product::retrieve($sku->product);

      if ($sku->inventory->quantity >= 1)
      {
        Cart::add($id, $product->name, 1, $sku->price);
      }

      return redirect()->action('ShopController@displayCart');
    }

    public function removeFromCart($rowId)
    {
      Cart::remove($rowId);
      return redirect()->action('ShopController@displayCart');
    }

    public function paymentPage()
    {
      $content = Cart::content();
      $details = [
        "content" => $content,
        "subtotal" => str_replace(',', '', Cart::subtotal()) / 100,
        "total_cents" => intval(str_replace(',', '', Cart::subtotal()))
      ];

      return view('shop.payment')->with($details);
    }

    public function purchase(Request $request)
    {
      \Stripe\Stripe::setApiKey(env('STRIPE_SECRET'));

      // Token is created using Stripe.js or Checkout!
      // Get the payment token submitted by the form:
      $user = Auth::user();

      if ($user->stripe_token == null)
      {
        $token = $_POST['stripeToken'];

        // Create a Customer:
        $customer = \Stripe\Customer::create(array(
          "email" => $user->email,
          "source" => $token,
        ));
        $user->stripe_id = $customer->id;
        $user->stripe_token = $token;
        $user->save();
      }
      else {
        $customer = \Stripe\Customer::retrieve($user->stripe_id);
      }

      $charge = \Stripe\Charge::create(array(
        "amount" => $request->total,
        "currency" => "usd",
        "customer" => $customer->id,
        "description" => Cart::count()
      ));

      Notification::send($user, new purchased($charge));

      $cartItems = Cart::content();
      foreach ($cartItems as $cartItem)
      {
        $sku = \Stripe\SKU::retrieve($cartItem->id);
        if ($sku->inventory->quantity > $cartItem->qty)
        {
          $sku->inventory->quantity = $sku->inventory->quantity - $cartItem->qty;
          $sku->save();
        }
      }

      Cart::destroy();

      return redirect()->action('HomeController@index');
    }
}
